<?php

namespace Verba\Act;

class Delete extends Action
{
    protected $iids = array();
    protected $processId;
    protected $level = 0;
    public $result = array('obj' => 0, 'lnk' => 0);
    protected $objects;

    protected $poh;
    protected $piid;
    protected $pLinkAlias;

    function __construct($processId = false)
    {
        $this->processId = !$processId
            ? \Verba\Hive::make_random_string(5, 5, 'l')
            : $processId;
    }

    function setIids($iids)
    {
        if (!\Verba\reductionToArray($iids)) return false;
        $this->iids = $iids;
    }

    function getIids()
    {
        return $this->iids;
    }

    function setLevel($val)
    {
        $this->level = (int)$val;
    }

    function getLevel()
    {
        return $this->level;
    }

    function delete_objects($iids = false)
    {
        try {
            if ($iids) {
                $this->setIids($iids);
            }

            if ($this->level == 0 && $this->pot && $this->piid) {
                $this->findIidsByParent();
            }

            $this->level++;
            if (!$this->iids && !$this->extractIidsFromSelected()) {
                throw new \Exception('Noting to delete - objects list is empty.');
            }
            $U = \Verba\User();
            if (!$this->loadObjects() && !$U->in_group(USR_ADMIN_GROUP_ID)) {
                throw new \Exception('Unable to load objects');
            }

            if (count($this->objects) && !$this->validateAccessAndRunHandlers()) {
                throw new \Exception('Access denied');
            }

            //Формирование перечня связанных объектов
            if (is_array($cAOT = $this->oh->getFamilyOTs('down'))) {
                //Если есть объявленные правила связи - обработка условия links_only
                if (count($Excl_OTs = $this->oh->getLinksOnlyDeleteOts())) {
                    $cAOT = array_diff($cAOT, $Excl_OTs);
                }
                $cAOT = array_unique($cAOT);
            }

            // Получение ветки связей
            $scan_result = \Verba\Branch::get_branch(array($this->ot_id => array('iids' => $this->iids, 'aot' => $cAOT)), 'down', 1, false, false, null, false);
            if (is_array($scan_result['handled']) && !empty($scan_result['handled'])) {
                foreach ($scan_result['handled'] as $linked_ot => $linked_iids) {
                    $deleter = \Verba\_oh($linked_ot)->initDelete($this->processId);
                    $deleter->setLevel($this->level);
                    $r = $deleter->delete_objects($linked_iids);
                }
            }

            $this->result['obj'] += (int)$this->execQuery();
            //remove links with possible parents
            if ($this->level == 1
                && is_array($upperOTs = $this->oh->getFamilyParents()) && count($upperOTs)) {
                foreach ($upperOTs as $unl_ot => $unl_ot_props) {
                    foreach ($unl_ot_props['rules'] as $u_rule_id => $u_rule) {
                        list($aff, $log) = $this->oh->unlink($this->iids, $unl_ot, $u_rule['alias'], 2);//,'up'
                        $this->result['lnk'] += (int)$aff;
                    }
                }
            }
            //remove links with childs
            if (is_array($downOTs = $this->oh->getFamilyChilds()) && count($downOTs)) {
                foreach ($downOTs as $unl_ot => $unl_ot_props) {
                    foreach ($unl_ot_props['rules'] as $u_rule_id => $u_rule) {
                        list($aff, $log) = $this->oh->unlink($this->iids, $unl_ot, $u_rule['alias'], 1);//,'down'
                        $this->result['lnk'] += $aff;
                    }
                }
            }

            $this->log()->event('Deleter #' . $this->processId . ' success. OT ' . $this->oh->getCode() . ' Deleted: objects - ' . $this->result['obj'] . ', links - ' . $this->result['lnk']);
            return $this->result;
        } catch (\Exception $e) {
            $this->log()->error('Deleter #' . $this->processId . ' ' . $e->getMessage() . "[" . $e->getTraceAsString() . "]");
            return false;
        }
    }

    function loadObjects()
    {
        $this->objects = $this->oh->getData($this->iids, true, true, false, false, false);
        if (!$this->objects) {
            $this->objects = array();
            return false;
        }
        return true;
    }

    function validateAccessAndRunHandlers()
    {
        global $S;
        if (!count($this->objects)) {
            return;
        }
        $ahandlers = $this->getAttrsHandlers();
        $U = $S->U();
        foreach ($this->objects as $iid => $row) {

            if (!$U->chrItem($row['key_id'], 'd', $row)) {
                unset($this->objects[$iid]);
                continue;
            }

            if ($ahandlers) {
                foreach ($ahandlers as $attr_id => $aths) {
                    $aths_keys = array_keys($aths);
                    $result = null;
                    $next_item_num = 0;
                    /**
                     * @var $Handler \Verba\Act\Delete\Handler
                     */
                    foreach ($aths as $set_id => $set_data) {
                        try {
                            if(!is_string($set_data['ah_name']) || !$set_data['ah_name']) {
                                $this->log()->error('Bad handler data for ahl set_id:' . var_export($set_id, true));
                                continue;
                            }
                            if($set_data['ah_name']{0} === '\\'){
                                $handlerClass = $set_data['ah_name'];
                            }else{
                                $handlerClass = '\Verba\Act\Delete\Handler\\'.$set_data['ah_name'];
                            }

                            if (!class_exists($handlerClass)) {
                                $this->log()->error("Unexists Delete Handler Class " . var_export($handlerClass, true));
                                continue;
                            }
                            $A = $this->oh->A($attr_id);
                            $Handler = new $handlerClass($this->oh, $A,
                                [
                                    'value' => $result,
                                    'A' => $A,
                                    'row' => $row,
                                    'set_data' => $set_data
                                ], $this);

                            $result = $Handler->run();
                            //$result = $this->$method($this->oh, $this->oh->A($attr_id), $row, $set_id, $set_data, $result);
                            if ($result !== false && $aths[$aths_keys[++$next_item_num]]['logic'] != 1) {
                                break;
                            }
                        } catch (\Exception $e) {
                            $this->log()->error($e->getMessage() . "[" . $e->getTraceAsString() . "]");
                        }
                    }
                }
            }
        }
        $this->setIids(array_keys($this->objects));
        return (bool)count($this->iids);
    }

    function getAttrsHandlers()
    {
        $r = [];
        foreach ($this->oh->getAttrs(true) as $attr_id => $attr_code) {
            $A = $this->oh->A($attr_id);
            if (is_array($aths = $A->getHandlers('delete')) && !empty($aths)) {
                $r[$attr_id] = $aths;
            }
        }
        return count($r) ? $r : false;
    }

    function extractIidsFromSelected()
    {
        // Получение ранее выбранных объектов из селекшена
        if (isset($_REQUEST['slID'])) {
            if ($sl = \Verba\init_selection($this->ot_id, false, $_REQUEST['slID'])) {
                $this->iids = array_unique(array_merge($sl->getSelected($this->ot_id), $this->iids));
            }
        }
        return is_array($this->iids) && count($this->iids) ? true : false;
    }

    function execQuery()
    {
        $_oh = \Verba\_oh($this->ot_id);

        if (!$iid_statement = $this->DB()->makeWhereStatement($this->iids, $_oh->getPAC()))
            return false;

        $query2exec =
            "DELETE    FROM " . $_oh->vltURI() . " WHERE `ot_id`='" . $this->ot_id . "' && (" . $iid_statement . ")";

        $oRes = $this->DB()->query($query2exec);
        if (!is_object($oRes)) {
            return false;
        }

        return $oRes->getAffectedRows();
    }

    function setParent($pot, $piid, $pLinkAlias = false)
    {
        $this->poh = \Verba\_oh($pot);
        $this->piid = $piid;
        $this->pLinkAlias = $pLinkAlias;
    }

    function findIidsByParent()
    {

        $rule = $this->oh->getRule($this->poh, $this->pLinkAlias);
        $pac = $this->oh->getPAC();
        if ($rule == 'fid') {
            $q = "SELECT " . $pac . " as `iid` FROM " . $rule['uri'] . " WHERE
" . $rule['glueField'] . " = '" . $this->piid . "'";
        } elseif ($rule == 'links_table') {
            $q = "SELECT `ch_iid` as `iid` FROM " . $rule['uri'] . " WHERE
  p_ot_id = '" . $this->poh->getID() . "'
  && ch_ot_id = '" . $this->oh->getID() . "'
  && p_iid = '" . $this->piid . "'";
        } else {
            return;
        }
        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getNumRows()) {
            return;
        }
        while ($row = $sqlr->fetchRow()) {
            $this->iids[] = $row['iid'];
        }
    }
}
