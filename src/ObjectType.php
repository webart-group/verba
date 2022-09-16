<?php
namespace Verba;

class ObjectType extends Base
{
    public $id;
    public $baseId;
    public $baseOT;
    public $OItemClassName;
    private $_ancestors = null;

    public $code;
    public $prim_attr_id;
    public $prim_attr_code;
    public $vlt_id;
    private $_vlt_id;
    public $stringPAC;
    public $stringPAID;
    public $base_key;
    public $ownerAttributeId = false;
    public $display;
    public $role;
    public $handler;
    public $family = array('parents' => array(),
        'childs' => array());
    public $links_rules = array();
    public $links_rules_delete = array();
    public $links_alias = array();
    public $vaults = array();
    public $behaviors = array(
        'not_searchable' => array(),
        'obligatory' => array(),
        'avtofield' => array(),
        'predefined' => array(),
        'not_editable' => array(),
        'hidden_edit' => array(),
        'hidden_new' => array(),
        'not_selectable' => array(),
        'foreign_id' => array(),
        'unique' => array(),
        'delayCreate' => array(),
        'lcd' => array(),
        'custom' => array());
    public $attr_binds = array();
    public $attributes = array();
    public $props = array();
    public $props_binds = array();

    protected $oh;

    function __construct($oh, $ot_id, $baseId = false)
    {

        $this->oh = $oh;

        $this->set_id($ot_id);
        if (is_numeric($baseId)) {
            $this->baseId = intval($baseId);
            $this->baseOT = \Verba\_oh($baseId)->OT;
        }
        $this->loadOTypeData();

        // Получение Атрибутов
        $this->loadAttributes();
        // Подгрузка Свойств
        $this->loadProps();

        $this->prim_attr_code = $this->attributes[$this->prim_attr_id]->attr_code;

        // String Id Attr Id
        if ($this->stringPAC) {
            $this->stringPAID = $this->code2id($this->stringPAC);
            if (!$this->stringPAID) {
                $this->log()->error('Invalid OT stringIdCode [' . var_export($this->stringPAC, true) . ']');
                $this->stringPAC = null;
            }
        }

        //обработчики атрибутов по умолчанию
        $this->loadAttrDefaultHandlers();

        //семья, правила связей
        $this->loadFamily();
        //$this->loadDeleteRules();
    }

    function __sleep()
    {
        $vars = get_object_vars($this);
        unset($vars['DB'], $vars['log']);
        return array_keys($vars);
    }

    function getOh()
    {
        return $this->oh;
    }

    function getAncestors()
    {
        if ($this->_ancestors === null || !is_array($this->_ancestors)) {
            $this->_ancestors = array();
            if ($this->baseOT) {
                $this->_ancestors = $this->baseOT->getAncestors();
                $this->_ancestors[$this->baseOT->getID()] = (int)$this->baseOT->getID();
            }
        }
        return $this->_ancestors;
    }

    function getDescendants()
    {
        global $S;
        return $S->getOtDescendants($this->id);
    }

    function loadOTypeData()
    {

        $query = "SELECT `ot`.`ot_id`
, `ot`.`ot_code`
, `ot`.`base_key`
, `ot`.`prim_attr_id`
, `ot`.`vlt_id`
, `ot`.`stringPAC`
, `ot`.`owner`
, `ot`.`handler`
, `ot`.`role`
, `ot`.`OItemClassName`
, `ot`.`title_" . SYS_LOCALE . "` as `display`
, GROUP_CONCAT(DISTINCT
  CONCAT_WS(':'
    , CAST(`v`.`vlt_id` AS CHAR)
    , '0'
    , `v`.`scheme`
    , `v`.`host`
    , `v`.`root`
    , `v`.`object`
    , CAST(`v`.`priority` AS CHAR)
    , `v`.`user`
    , `v`.`password`
    , CAST(`v`.`port` AS CHAR)
  )
    SEPARATOR '#'
) as vaults
FROM `" . SYS_DATABASE . "`.`_obj_types` as `ot`
LEFT JOIN `" . SYS_DATABASE . "`.`_obj_data_vaults` v
  ON ot.vlt_id = v.vlt_id
WHERE  `ot`.`id` = " . $this->id . "
GROUP BY ot.id";

        if (!($oRes = $this->DB()->query($query)) || ($oRes->getNumRows() < 1)) {
            throw new Exception('Cant load OT data');
        }

        $data = $oRes->fetchRow();
        $this->set_code($data['ot_code']);

        $this->set_base_key(!$data['base_key'] && is_object($this->baseOT)
            ? $this->baseOT->base_key
            : $data['base_key']
        );

        $this->set_prim_attr_id(!$data['prim_attr_id'] && is_object($this->baseOT)
            ? $this->baseOT->prim_attr_id
            : $data['prim_attr_id']
        );

        $this->setStringPAC(!$data['stringPAC'] && is_object($this->baseOT)
            ? $this->baseOT->stringPAC
            : $data['stringPAC']
        );
        $this->setHandler($data['handler']);
        $this->setOwnerAttributeId(!$data['owner'] && is_object($this->baseOT)
            ? $this->baseOT->ownerAttributeId
            : $data['owner']
        );

        $this->setDataVaults($data['vaults']);
        $this->set_display($data['display']);

        $this->setRole($data['role']);

        $this->setOItemClassName($data['OItemClassName']);

        return true;
    }


    function loadAttributes()
    {
        global $S;

        $ots = array_merge($this->getAncestors());

        array_push($ots, $this->id);
        $query =
            "SELECT 
  `a`.*
  , `a`.`title_ru` as `display`
FROM `" . SYS_DATABASE . "`.`_obj_attributes` as `a`
WHERE `a`.`ot_iid` IN('" . implode("','", $ots) . "')
GROUP BY `a`.`attr_id`
ORDER BY `a`.`priority`
";

        if (!($oRes = $this->DB()->query($query)) || ($oRes->getNumRows() < 1)) {
            return false;
        }
        $rows = [];
        while ($row = $oRes->fetchRow()) {
            $row['_ot_iid'] = $row['ot_iid'];
            $row['ot_iid'] = $this->id;

            if(array_key_exists($row['attr_code'], $rows))
            {
                $rows[$row['attr_code']] = array_replace_recursive($rows[$row['attr_code']], $row);
            }
            else
            {
                $rows[$row['attr_code']] = $row;
            }
        }

        foreach($rows as $row)
        {
            $this->attributes[$row['attr_id']] = \Verba\ObjectType\Attribute::create($this, $row);
            $this->attr_binds[$row['attr_code']] = $row['attr_id'];

            // формирование бихавиоров
            foreach ($this->behaviors as $behavior => $saveTo) {
                if ($row[$behavior]) {
                    $this->behaviors[$behavior][$row['attr_id']] = $row['attr_code'];
                }
            }
        }

        $attrs = $this->getAttrsByBehaviors('predefined');
        if (is_array($attrs) && count($attrs)) {

            $ots = array_merge($ots, $this->getDescendants());

            array_push($ots, '');

            $attr_ot_id = $S->otSomeToId('ot_attribute');
            $pdset_ot_id = $S->otSomeToId('pd_set');
            $query =
                "SELECT 
  `pd_attr_l`.`ch_iid` AS `pd_set`
  , `pd_attr_l`.`p_iid` AS `attr_id` 
  , `pd_attr_l`.`default_value` AS `default_value` 
  , `pd_attr_l`.`rule_alias` AS `ot_id` 
  , `ps`.`title` as `pd_set_title`
  , `dv`.`vlt_id` as `vlt_id`
  , `dv`.`root` as `root`
  , `dv`.`object` as `object`
FROM `" . SYS_DATABASE . "`.`pd_sets_attrs_links` as `pd_attr_l`

LEFT JOIN `" . SYS_DATABASE . "`.`pd_sets` `ps`
ON `ps`.`id` = `pd_attr_l`.ch_iid

LEFT JOIN `" . SYS_DATABASE . "`.`_obj_data_vaults` as `dv`
ON `dv`.`vlt_id` = ps.vlt_id

WHERE `pd_attr_l`.`rule_alias` IN('" . implode("','", $ots) . "')
&& `pd_attr_l`.`p_ot_id` = '" . $attr_ot_id . "' 
&& `pd_attr_l`.ch_ot_id = '" . $pdset_ot_id . "'
&& `pd_attr_l`.`p_iid` IN ('" . implode("','", array_keys($attrs)) . "')
-- GROUP BY `pd_attr_l`.`p_iid`
";

            $sqlr = $this->DB()->query($query);
            if ($sqlr && $sqlr->getNumRows()) {
                while ($row = $sqlr->fetchRow()) {
                    $A = $this->A($row['attr_id']);
                    if (!$A) {
                        continue;
                    }
                    $A->PdCollection()->add($row);
                }

            }
        }

    }

    function loadAttrDefaultHandlers()
    {
        if (!is_array($this->attributes) || !count($this->attributes)) {
            return false;
        }
        $attr_ids = $this->DB()->makeWhereStatement(array_keys($this->attributes), 'p_iid', 'ahl');
        $ots = $this->getAncestors();
        array_push($ots, $this->id, '');


        $query = "SELECT ahl.p_iid as attr_id, 
 ahl.priority, 
 ahl.ch_iid as ah_id, 
 ahl.set_id, ahl.logic,
 `ah`.`ah_name`, 
 `ah`.`check_params`, 
 `ah`.`ah_type`, 
 `ahl`.`cfg` as `cfg`, 
 `aht`.`ah_type_name`
    FROM `" . SYS_DATABASE . "`.`_ath_links` as `ahl`,
         `" . SYS_DATABASE . "`.`_ath` as `ah`,
         `" . SYS_DATABASE . "`.`_ath_types` as `aht`
    WHERE
    ( " . $attr_ids . " )
    && `ahl`.`rule_alias` IN('" . implode("','", $ots) . "')
    && `ahl`.`ch_iid` = `ah`.`ah_id` && `ah`.`ah_type` = `aht`.`id`
    ORDER BY `ahl`.`p_iid`, `ahl`.`priority`";

        if (!($res = $this->DB()->query($query)) || $res->getNumRows() < 1) {
            return false;
        }

        $sets = array();
        $ath_needParams = array();
        while ($row = $res->fetchRow()) {
            $setId = $row['set_id'];
            $attr_id = $row['attr_id'];
            $action = $row['ah_type_name'];
            if (!array_key_exists($action, $this->attributes[$attr_id]->handlers)) {
                $this->attributes[$attr_id]->handlers[$action] = array();
            }

            $sets[$setId] = array('a' => $attr_id, 't' => $action);
            if ($row['check_params'] == '1') {
                $ath_needParams[$row['ah_name']][$setId] = $attr_id;
            }
            unset($row['check_params'], $row['attr_id']);
            if (!empty($row['cfg'])) {
                $row['params'] = json_decode($row['cfg'], JSON_OBJECT_AS_ARRAY);
            } else {
                $row['params'] = array();
            }
            $this->attributes[$attr_id]->handlers[$action][$setId] = $row;
        }

        if (is_array($ath_needParams) && count($ath_needParams) > 0) {

            foreach ($ath_needParams as $ruleName => $set_ids) {

                if (!is_array($byRule = $this->getAthParams($ruleName, array_keys($set_ids)))) {
                    continue;
                }

                foreach ($byRule as $set_id => $params) {
                    $cset = &$this->attributes[$sets[$set_id]['a']]->handlers[$sets[$set_id]['t']][$set_id];
                    $cset['params'] = array_replace_recursive($params, $cset['params']);
                }
            }
        }

        return true;
    }

    function loadProps()
    {

        $where_stm = "`a`.`ot_iid` = " . $this->id;

        $query = "
SELECT 
`a`.*,
`a`.`title_" . SYS_LOCALE . "` as `display`
FROM `" . SYS_DATABASE . "`.`_obj_props` as `a`
WHERE " . $where_stm . "
ORDER BY `a`.`priority`";

        if (!($oRes = $this->DB()->query($query)) || ($oRes->getNumRows() < 1)) {
            return false;
        }

        while ($row = $oRes->fetchRow()) {
            $this->props[$row['id']] =  \Verba\ObjectType\Property::createProperty($row, $this);
            $this->props_binds[$row['code']] = $row['id'];
            // формирование бихавиоров
        }
    }

    function loadFamily()
    {
        global $S;
        $oids = array($this->id);
//    if($this->baseOT){
//      $oids[] = $this->baseId;
//    }
        $query =
            "SELECT DISTINCT
`lrules`.`rule_id`,
`lrules`.`alias`,
`lrules`.`priority`,
`lrules`.`rule`,
`lrules`.`statement`,
`lrules`.`p_ot_id` as po,
`lrules`.`ch_ot_id` as so,
`lrules`.`links_table`,
`lrules`.`db`,
`lrules`.`del_links_only`
FROM
`" . SYS_DATABASE . "`.`_obj_links_rules` as `lrules`
WHERE
`lrules`.`p_ot_id` IN ('" . implode("','", $oids) . "')
|| `lrules`.`ch_ot_id`  IN ('" . implode("','", $oids) . "')
ORDER BY `priority` DESC";
        $oRes = $this->DB()->query($query);
        if (!$oRes || $oRes->getNumRows() < 1) {
            return false;
        }

        while ($row = $oRes->fetchRow()) {

            if (empty($row['db'])) {
                $row['db'] = SYS_DATABASE;
            }

            $po = $this->id == $row['po']
                ? $this->id
                : $row['po'];
            $so = $this->id == $row['so']
                ? $this->id
                : $row['so'];

            $rule_id = $row['rule_id'];
            $alias = !empty($row['alias']) ? $row['alias'] : false;

            if ($po == $this->id) { //If currentOt is Parent
                $part = 'childs';
                $opart = 'parents';
                $linked_id = $so;
                $this->links_rules_delete[$linked_id] = array('links_only' => $row['del_links_only']);
            }

            if ($so == $this->id) { //If currentOt is Child
                $part = 'parents';
                $opart = 'childs';
                $linked_id = $po;
            }

            switch ($row['rule']) {
                case 'links_table':
                    $db = empty($row['db']) ? SYS_DATABASE : $row['db'];
                    $table = empty($row['links_table']) ? 'obj_links' : $row['links_table'];

                    $this->links_rules[$linked_id][$rule_id] = array(
                        'rule' => $row['rule'],
                        'id' => $rule_id,
                        'statement_value' => $table,
                        'db' => $db,
                        'table' => $table,
                        'uri' => '`' . $db . '`.`' . $table . '`',
                        'alias' => $alias,
                        'links_table' => $table,
                    );
                    break;

                case 'fid':
                    $exploded = explode(',', $row['statement']);
                    if (count($exploded) > 1) {
                        $iid_field = trim($exploded[1]);
                        $ot_field = trim($exploded[0]);
                    } else {
                        $iid_field = trim($exploded[0]);
                        $ot_field = null;
                    }
                    $this->links_rules[$linked_id][$rule_id] = array(
                        'rule' => $row['rule'],
                        'id' => $rule_id,
                        'ot_field' => $ot_field,
                        'glue_field' => $iid_field,
                        'db' => -1,
                        'table' => $row['links_table'],
                        'uri' => -1,
                        'sec' => $part == 'childs' ? $linked_id : $this->id,
                        'alias' => $alias,
                        'inverted' => !empty($row['links_table']) && $row['links_table'] == $this->id
                            ? true
                            : false
                    );
            }

            if (!isset($this->family[$part][$linked_id])) {
                $this->family[$part][$linked_id] = array(
                    'code' => $S->otIdToCode($linked_id),
                    'rules' => array(),
                );
            }

            $this->family[$part][$linked_id]['rules'][$rule_id] = &$this->links_rules[$linked_id][$rule_id];

            if ($po == $so) {
                if (!isset($this->family[$opart][$linked_id])) {
                    $this->family[$opart][$linked_id] = array(
                        'code' => $S->otIdToCode($linked_id),
                        'rules' => array(),
                    );
                }
                $this->family[$opart][$linked_id]['rules'][$rule_id] = &$this->links_rules[$linked_id][$rule_id];
            }

            if (!empty($alias)) {
                $this->links_alias[$alias] = &$this->links_rules[$linked_id][$rule_id];
            }
        }

        foreach (array('parents', 'childs') as $part) {
            foreach ($this->family[$part] as $ot => $data) {
                if (!is_array($des = $S->getOtDescendants($ot)) || empty($des)) {
                    continue;
                }
                foreach ($des as $dot) {
                    $this->family[$part][$dot] = array(
                        'code' => $S->otIdToCode($dot),
                        'rules' => &$this->family[$part][$ot]['rules']
                    );
                }
            }
        }
    }

    function loadDeleteRules()
    {
        $query = "SELECT * FROM `" . SYS_DATABASE . "`.`_obj_links_rules_delete` WHERE p_ot = '" . $this->id . "'";
        $oRes = $this->DB()->query($query);
        if (!$oRes || $oRes->getNumRows() < 1)
            return false;
        while ($row = $oRes->fetchRow()) {
            $this->links_rules_delete[$row['ch_ot']] =
                array('links_only' => $row['links_only'],
                    'deep' => $row['deep']);
        }
        return true;
    }

    function getID()
    {
        return $this->id;
    }

    function is_attribute($needle, $own = false)
    {
        if (is_numeric($needle) && array_key_exists($needle, $this->attributes)) {
            return true;
        }
        if (is_string($needle) && array_key_exists($needle, $this->attr_binds)) {
            return true;
        }

        if (!$own && is_object($this->baseOT)) {
            return $this->baseOT->is_attribute($needle);
        }

        return false;
    }

    function getAttrsByRole($role/*, $own = false*/)
    {
//    if(!$own && $this->_base){
//      $r = $this->_base->getAttrsByRole($role, $own);
//    }else{
//      $r = array();
//    }
        $r = array();
        foreach ($this->attributes as $aid => $A) {
            if (!$A->inRole($role)) {
                continue;
            }
            $r[] = $A->getCode();
        }
        return $r;
    }

    /**
     * Возвращает объект атрибута
     * @param mixed $attr_id код или id атрибута
     * @return bool|\Verba\ObjectType\Attribute
     * @see \Verba\ObjectType\Attribute
     */

    function A($attr, $own = false)
    {
        $A = null;
        if (is_object($attr) && $attr instanceof \Verba\ObjectType\Attribute) {

            $A = $this->isA($attr->getID(), $own) ? $attr : false;

        } elseif (!is_numeric($attr) && is_string($attr)) {

            $attr = (int)$this->code2id($attr);

        }

        if (!$A && $attr && array_key_exists($attr, $this->attributes)) {
            $A = $this->attributes[$attr];
        }
        if (!$A && !$own && $this->baseOT) {
            $A = $this->baseOT->A($attr);
        }

        if (is_object($A) && is_object($this->oh)) {
            $A->setCallContext($this->oh);
        }

        return $A;
    }

    function isA($needle, $own = false)
    {
        if (is_object($needle) && $needle instanceof \Verba\ObjectType\Attribute) {
            $needle = $needle->getID();
        }
        if (is_string($needle) && array_key_exists($needle, $this->attr_binds)) {
            return true;
        }
        if (is_numeric($needle) && array_key_exists($needle, $this->attributes)) {
            return true;
        }
        if (!$own && $this->baseOT) {
            return $this->baseOT->isA($needle, $own);
        }
        return false;
    }

    /**
     * Возвращает массив атрибутов соответствующих заданным условиям
     * @param mixed $attr_list Входящие атрибуты. Может быть: массивом или строкой - кода(ов) атрибутов; true = все возможные атрибуты, false - только примариАттр
     * @param string|array $allowed_behaviors Массив или строка содержащая группы атрибуты из которых могут присутствовать в результате
     * @param string|array $denied_behaviors Массив cодержащая группы (или одну группу если строка) атрибутовы из которых Не могут присутствовать в результате
     * @param string|array $rights Если передано строкой или массивом, то атрибуты имеющие restrict_key будут просеяны на допуск по переданному этому праву
     *
     * @return false|array
     */
    function getAttrs($attr_list = false, $allowed_behaviors = false, $denied_behaviors = false, $rights = false)
    {

//    if($this->_base){
//      $_base_attrs = $this->_base->getAttrs($attr_list, $allowed_behaviors, $denied_behaviors, $rights);
//    }else{
//      $_base_attrs = false;
//    }

        if (is_string($attr_list)) {
            $attr_list = array($attr_list);
        }

        if (\Verba\reductionToArray($attr_list)) {
            $r = array();

            foreach ($attr_list as $attr_code) {
                $A = $this->A($attr_code);
                if (!$A) {
                    continue;
                }
                $r[$A->getID()] = $A->getCode();
            }
            $attr_list = $r;

        } elseif ($attr_list === false) {

            $attr_list = array($this->prim_attr_id => $this->prim_attr_code);

        } elseif ($attr_list === true) {

            $attr_list = $this->getAttrsPlainList(true);

        } else {
            return false;
        }

        if (\Verba\reductionToArray($denied_behaviors)
            && count($denied = $this->getAttrsByBehaviors($denied_behaviors, true))) {
            $attr_list = array_diff_key($attr_list, $denied);
        }
        if (\Verba\reductionToArray($allowed_behaviors)
            && count($allowed = $this->getAttrsByBehaviors($allowed_behaviors, true))) {
            $attr_list = array_intersect_key($attr_list, $allowed);
        }

        // Доступ к атрибуту по правам
        if (\Verba\reductionToArray($rights) && count($attr_list)) {
            $U = \Verba\User();
            foreach ($attr_list as $attr_id => $attr_code) {
                $A = $this->A($attr_id, true);
                if ($A->restrict_key != 0 && is_int($A->restrict_key) && !$U->chr($A->restrict_key, $rights)) {
                    unset($attr_list[$attr_id]);
                }
            }
        }
//
//    if(is_array($_base_attrs) && count($_base_attrs)){
//      $attr_list = $_base_attrs + $attr_list;
//    }

        return count($attr_list)
            ? $attr_list
            : false;
    }

    function getAttrsPlainList($onlyOwn = false)
    {

//    if(!$onlyOwn && $this->baseOT){
//      $base_r = $this->baseOT->getAttrs();
//    }else{
//      $base_r = array();
//    }
//
//    $r = $base_r + array_flip($this->attr_binds);

        return array_flip($this->attr_binds);//$r;
    }

    function code2id($attr_code, $own = false)
    {
        if (array_key_exists($attr_code, $this->attr_binds)) {
            return $this->attr_binds[$attr_code];
        }
        if (!$own && $this->baseOT) {
            return $this->baseOT->code2id($attr_code, $own);
        }
        return false;
    }

    function id2code($attr_id, $own = false)
    {
        if (array_key_exists($attr_id, $this->attributes)) {
            return $this->attributes[$attr_id]->getCode();
        }
        if (!$own && $this->baseOT) {
            return $this->baseOT->id2code($attr_id, $own);
        }
        return false;
    }

    function setOwnerAttributeId($owner_attr_id)
    {
        if (is_int($owner_attr_id = intval($owner_attr_id)) && $owner_attr_id > 0) {
            $this->ownerAttributeId = $owner_attr_id;
        }
    }

    function is_lcd($attr_id)
    {

        if (!is_numeric($attr_id) && is_string($attr_id)) {
            $attr_id = $this->code2id($attr_id);
        }

        return is_numeric($attr_id) && isset($this->attributes[$attr_id]) && is_object($this->attributes[$attr_id]) && $this->attributes[$attr_id]->lcd == 1 ? true : false;
    }

    function getAthParams($rule, $set_ids)
    {
        $result = false;

        if (false !== ($where_sets = $this->DB()->makeWhereStatement($set_ids, 'set_id'))) {
            $table_name = '_athp_' . str_replace('\\', '_', strtolower($rule));
            $query = "SELECT * FROM `" . SYS_DATABASE . "`.`" . $table_name . "` WHERE (_ot_id = '" . $this->id . "' || _ot_id = '0') && " . $where_sets . " ORDER BY `priority`";

            if (!($oRes = $this->DB()->query($query)) || $oRes->getNumRows() < 1) {
                return false;
            }
            while ($row = $oRes->fetchRow()) {
                if (!isset($result[$row['set_id']])
                    || $row['_ot_id'] == $this->id) {
                    $result[$row['set_id']] = $row;
                }
                unset($result[$row['set_id']]['set_id']);
            }
        }

        return $result;
    }

    function setDataVaults($vaults)
    {
        global $S;

        if (!is_string($vaults) || !$vaults || count($vaults = explode('#', $vaults)) < 1) {
            if (is_object($this->baseOT)) {
                $vltId = $this->baseOT->vlt_id;
                $this->set_vlt_id($vltId);
                $this->vaults = $this->baseOT->vaults;
                $this->_vlt_id = false;
                return true;
            }
            return false;
        } else {

            $vaultFields = array('vlt_id',
                'key_id',
                'scheme',
                'host',
                'root',
                'object',
                'priority',
                'user',
                'password',
                'port',
            );
            foreach ($vaults as $k => $v) {
                if (empty($v)) {
                    continue;
                }

                $v = explode(':', $v);
                if (count($vaultFields) !== count($v)) {
                    continue;
                }

                $combined = array_combine($vaultFields, $v);
                if ($S->addDataVault($combined['vlt_id'], $combined)) {
                    $this->vaults[$combined['key_id']] = $S->getDataVaultById($combined['vlt_id'], false);
                    if ($combined['key_id'] == 0) {
                        $this->set_vlt_id($combined['vlt_id']);
                    }
                }
            }
        }
    }

    function getRawVltId()
    {
        return $this->_vlt_id;
    }

    function set_id($val)
    {
        $this->id = (int)$val;
    }

    function set_code($val)
    {
        $this->code = $val;
    }

    function set_base_key($val)
    {
        $this->base_key = (int)$val;
    }

    function set_prim_attr_id($val)
    {
        $this->prim_attr_id = (int)$val;
    }

    function setStringPAC($val)
    {
        if (!settype($val, 'string')) {
            return false;
        }
        $this->stringPAC = $val;
    }

    function set_vlt_id($val)
    {
        $this->vlt_id = (int)$val;
        $this->_vlt_id = $this->vlt_id;
    }

    function set_display($val)
    {
        $this->display = $val;
    }

    function setRole($val)
    {
        $this->role = $val;
    }

    function setHandler($val)
    {
        $this->handler = (string)$val;
    }

    function setOItemClassName($var)
    {
        if (!is_string($var) || !$var) {
            return false;
        }
        $this->OItemClassName = $var;
        return $this->OItemClassName;
    }

    function getOItemClassName()
    {
        return $this->OItemClassName;
    }

    function getHandler()
    {
        return $this->handler;
    }

    function getAttrsByBehaviors($behaviors, $own = false)
    {
        $r = array();
        if (is_string($behaviors)) {
            $behaviors = array($behaviors);
        }
        if (!is_array($behaviors) || !count($behaviors)) {
            return $r;
        }

        foreach ($behaviors as $behavior) {
            if (array_key_exists($behavior, $this->behaviors))
                $r += $this->behaviors[$behavior];
        }

        if (!$own && $this->baseOT) {
            $r += $this->baseOT->getAttrsByBehaviors($behaviors);
        }
        return $r;
    }
}
