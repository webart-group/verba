<?php

namespace Verba\Mod;

class Otype extends \Verba\Mod
{
    use \Verba\ModInstance;

    function clearOtCache()
    {
        global $S;
        $S->clearOtCache();
    }

    function cloneVault($source, $trg_vltName)
    {
        $_src = \Verba\_oh($source);

        $vlt = $_src->getVault();
        switch ($vlt->getScheme()) {
            case 'mysql':
                $mthd = 'cloneMySQLVault';
                break;
        }

        if (!isset($mthd) || !method_exists($this, $mthd)) {
            return false;
        }

        $r = $this->$mthd($vlt, $trg_vltName);
        if (!$r) {
            return false;
        }
        $_dv = \Verba\_oh('data_vault');
        $ae = $_dv->initAddEdit('new');
        $ae->setGettedObjectData(array(
            'scheme' => $vlt->getScheme(),
            'host' => $vlt->getHost(),
            'root' => $vlt->getRoot() == SYS_DATABASE ? '' : $vlt->getRoot(),
            'object' => $trg_vltName,
            'user' => $vlt->getUser(),
            'password' => $vlt->getPassword(),
            'port' => $vlt->getPort(),
        ));
        $ae->addedit_object();
        $cloned_vlt_id = $ae->getIID();
        return $cloned_vlt_id;
    }

    function cloneMySQLVault($src_vlt, $trg_vltName)
    {
        $trg_vltName = preg_replace('/[^a-z0-9_]/i', '', $trg_vltName);
        $src_uri = $src_vlt->getURI();
        $sqlr = $this->DB()->query("CREATE TABLE `" . SYS_DATABASE . "`.`" . $trg_vltName . "` LIKE " . $src_uri);
        if (!$sqlr || !$sqlr->getResult()) {
            $this->log()->error('Unable to CLONE MySQL OT Vault. Source Vlt: ' . var_export($src_vlt, true) . ', Target Vault Name: ' . $trg_vltName);
            return false;
        }
        return true;
    }

    /**
     * @param $oh \Model
     * @param $attr
     * @param bool $createIndex
     * @return array|bool
     */
    function addTableFieldForAttribute($oh, $attr, $createIndex = false)
    {

        $oh = \Verba\_oh($oh);
        $acode = $attr['attr_code'];
        $createIndex = (bool)$createIndex;

        $sqlr = $this->DB()->query("SHOW COLUMNS FROM " . $oh->vltURI() . " LIKE '" . $acode . "'");
        if ($sqlr->getAffectedRows() != 0) {
            $this->log()->error('Unable to create DB Table "' . var_export($oh->vltURI(), true) . '" field "' . $acode . '" - field already exists.');
            return false;
        }

        $columnMetaData = $this->getColumnTypeForAttr($attr['form_element'], $attr['data_type']);
        $unsigned = $columnMetaData['unsigned']
            ? ' UNSIGNED'
            : '';
        $lenght = !empty($columnMetaData['lenght'])
            ? ' (' . $columnMetaData['lenght'] . ')'
            : '';

        $default = '';
        if (!empty($columnMetaData['default'])) {
            $default = ' DEFAULT ';
            if (is_numeric($columnMetaData['default'])) {
                $default .= $columnMetaData['default'];
            } else {
                $default .= "'" . $this->DB()->escape($columnMetaData['default']) . "'";
            }
        }

        $vault_mask = '~~_vlt__~~';

        $q = "ALTER TABLE " . $vault_mask . " "
            . "ADD COLUMN `" . $acode . "` "
            . $columnMetaData['dbtype']
            . $lenght
            . $unsigned
            . " NOT NULL"
            . $default;

        if ($createIndex) {

            $index = '';
            if (isset($columnMetaData['index']) && !empty($columnMetaData['index'])) {
                $index = ' ' . $columnMetaData['index'];
            }
            $qi = "ALTER TABLE " . $vault_mask . " ADD" . $index . " INDEX si_" . $this->DB()->escape($acode) . " (" . $acode . ")";

        }

        $ohs = array($oh);
        $dsc = $oh->getDescendants();
        if (is_array($dsc) && count($dsc)) {
            foreach ($dsc as $dot) {
                $ohs[] = \Verba\_oh($dot);
            }
        }
        try {
            /**
             * @var $coh \Model
             */
            foreach ($ohs as $coh) {
                $sqlr = $this->DB()->query(
                    str_replace($vault_mask, $coh->vltURI(), $q)
                );
                if (!$sqlr->getResult()) {
                    $this->log()->error('Error while add DB Table field for attr: ' . var_export($acode, true) . ', ot: ' . var_export($coh->getCode(), true));
                    return false;
                }
                $this->log()->event('New Table \'' . $coh->vltURI() . '\' Field added for Attr: ' . var_export($acode, true) . ', ot: ' . var_export($coh->getCode(), true));


                if (isset($qi)) {
                    $sqlr = $this->DB()->query(
                        str_replace($vault_mask, $coh->vltURI(), $qi)
                    );
                    if (!$sqlr->getResult()) {
                        $this->log()->error('Unable to create Table Index for attr: ' . var_export($acode, true) . ', ot: ' . var_export($coh->getCode(), true));
                        return false;
                    }
                    $this->log()->event('ALTER TABLE \'' . $coh->vltURI() . '\' new Index added for Attr: ' . var_export($acode, true) . ', ot: ' . var_export($coh->getCode(), true));
                }
            }

        } catch (\Exception $e) {
            $this->log()->error('Bad Attr SQL ALTER TABLE Error: ' . var_export($acode, true) . ', ot: ' . var_export($oh->getCode(), true));
        }

        return $columnMetaData;
    }

    function getColumnTypeForAttr($fe, $dt)
    {

        if (!$dt || !$fe) {
            $this->log()->error('Unknown attr data_type [' . var_export($dt, true) . '] or form_element [' . var_export($fe, true) . ']');
            return false;
        }

        $dts = $this->gC('avaibleDataTypes');
        $fes = $this->gC('avaibleFormElements');

        $default = array(
            'dbtype' => 'varchar',
            'index' => '',
            'lenght' => '255',
            'default' => false,
            'unsigned' => false,
            'ah' => false,
        );

        if (!array_key_exists($fe, $fes)
            || !array_key_exists($dt, $dts)
        ) {
            $this->log()->error('Unknown FormElement [' . var_export($fe, true) . '] or DataType [' . var_export($dt, true) . ']');
            return false;
        }
        $feCfg = $fes[$fe];

        if (!empty($feCfg)) {
            if (array_key_exists('type', $feCfg)) {
                unset($feCfg['type']);
            }
        }

        $dtCfg = $dts[$dt];

        $ecfg = array_replace_recursive($dtCfg, $feCfg);
        if (!isset($ecfg['dbtype']) || empty($ecfg['dbtype'])) {
            $ecfg['dbtype'] = $dt;
        }

        $r = array_replace_recursive($default, $ecfg);

        $r = array_intersect_key($r, $default);

        return $r;

    }

    function cron_dropDeletedVault($tmpVaultName, $vlt)
    {
        $signal = 0;

        $q = "SELECT * FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA`='" . SYS_DATABASE . "' && `TABLE_NAME` = '" . $this->DB()->escape($tmpVaultName) . "'";
        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getNumRows()) {
            $this->log()->error('Temp Vault not found, Vault removation is interrupted. $tmpVaultName: ' . var_export($tmpVaultName, true) . ', $vlt: ' . var_export($vlt, true));
            return $signal;
        }

        $q = "DROP TABLE `" . SYS_DATABASE . "`.`" . $tmpVaultName . "`";
        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getResult()) {
            $this->log()->error('Unable to drop Temp Vault. $tmpVaultName: ' . var_export($tmpVaultName, true) . ', $vlt: ' . var_export($vlt, true));
        } else {
            $this->log()->error('Temp Vault dropped. $tmpVaultName: ' . var_export($tmpVaultName, true) . ', $vlt: ' . var_export($vlt, true));
        }
        return $signal;
    }

    function getJsDataTypes()
    {
        $r = $this->gC('avaibleDataTypes');
        foreach ($r as $k => $cfg) {
            unset($r[$k]['dbtype'],
                $r[$k]['unsigned'],
                $r[$k]['ah'],
                $r[$k]['index']
            );
        }
        return $r;
    }

    function getJsFormElements()
    {
        $r = $this->gC('avaibleFormElements');
        foreach ($r as $k => $cfg) {
            unset(
                $r[$k]['ah']
            );
        }
        return $r;
    }

    function getOAttrAhsTypes()
    {
        $r = array();

        $q = "SELECT * FROM " . SYS_DATABASE . ".`_ath_types` ORDER BY `ah_type_name`";
        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getNumRows()) {
            $this->log()->error('Unable to load AH types.');
            return $r;
        }


        while ($row = $sqlr->fetchRow()) {
            $r[$row['id']] = $row['ah_type_name'];
        }
        return $r;
    }

    function getAhsByTypes($ah_type_id)
    {

        $ah_type_id = (int)$ah_type_id;
        if (!$ah_type_id || $ah_type_id < 1) {
            return false;
        }

        $q = "SELECT * FROM " . SYS_DATABASE . ".`_ath` WHERE ah_type = '" . $ah_type_id . "' ORDER BY `ah_name` ASC";
        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getNumRows()) {
            return array();
        }

        $r = array();

        while ($row = $sqlr->fetchRow()) {
            $r['k' . $row['ah_id']] = $row;
        }

        return $r;
    }

    function assignAhToAttr($attr_id, $ah_id, $logic = 0, $priority = 0)
    {
        $attr_id = (int)$attr_id;
        $ah_id = (int)$ah_id;
        $logic = (int)$logic;
        $priority = (int)$priority;

        $_oattr = \Verba\_oh('ot_attribute');
        $_ah = \Verba\_oh('ah');


        $oattrData = $_oattr->getData($attr_id, 1);
        $ahData = $_ah->getData($ah_id, 1);
        try {
            if (!$oattrData
                || !$ahData
            ) {
                throw new \Exception('Attr or Ah entries not found');
            }

            $logic = !$logic || !in_array($logic, array(0, 1)) ? 0 : $logic;

            $r = $_oattr->link($attr_id, array($_ah->getID() => array($ah_id)), null, null, array('priority' => $priority, 'logic' => $logic));

            $row = false;
            // link inserted
            if ($r[0] > 0) {
                $q = "SELECT * FROM " . $_oattr->vltURI($_ah) . " WHERE `rule_alias` = ''
  && p_ot_id = '" . $_oattr->getID() . "'
  && ch_ot_id = '" . $_ah->getID() . "'
  && p_iid = '" . $attr_id . "'
  && ch_iid = '" . $ah_id . "'
         ";
                $sqlr = $this->DB()->query($q);
                if ($sqlr && $sqlr->getNumRows()) {
                    $row = $sqlr->fetchRow();
                }
            }
        } catch (\Exception $e) {
            return array(false, false);
        }

        return array($row, $ahData);
    }

    function unassignAhFromAttr($set_id)
    {
        $set_id = (int)$set_id;

        if (!is_int($set_id) || !$set_id) {
            $this->log()->error('Unable to unassign Ah - bad set_id');
            return false;
        }
        $_oattr = \Verba\_oh('ot_attribute');
        $_ah = \Verba\_oh('ah');

        $q = "SELECT * FROM " . $_oattr->vltURI($_ah) . " WHERE `set_id` = '" . $set_id . "'";

        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getNumRows()) {
            return false;
        }
        $row = $sqlr->fetchRow();
        $attr_id = $row['p_iid'];
        $ah_id = $row['ch_iid'];

        // link delete
        $r = $_oattr->unlink($attr_id, array($_ah->getID() => array($ah_id)), false, false);//, 1

        // try to remove set props if Ah have it
        $ahData = $_ah->getData($ah_id, 1);
        if ($ahData
            && isset($ahData['check_params']) && $ahData['check_params'] == 1
        ) {
            $ah_props_table_name = '_athp_' . str_replace('\\', '_', $ahData['ah_name']);
            $rmq = "DELETE FROM `" . SYS_DATABASE . "`.`" . $this->DB()->escape($ah_props_table_name) . "`
      WHERE set_id = '" . $set_id . "'";
            $sqlrr = $this->DB()->query($rmq, false);
            if (!$sqlrr || $sqlrr->getAffectedRows()) {
                $this->log()->error('Error while try to remove Ah props. set_id: ' . $set_id . ', ah_name: ' . $ahData['ah_name'] . ', athp_table: ' . $ah_props_table_name);
                return false;
            }
        }

        return true;
    }

    function getOtAttrsAsArray($ot_id)
    {
        $coh = \Verba\_oh($ot_id);
        $r = array();
        $attrs = $coh->getAttrs(true);
        if (!$attrs) {
            return $r;
        }

        foreach ($attrs as $attr_id => $attr_code) {
            $A = $coh->A($attr_code);

            $r[$attr_id] = array(
                'id' => $attr_id,
                'code' => $attr_code,
                'title' => $A->getTitle()
            );
        }
        return $r;
    }
}
