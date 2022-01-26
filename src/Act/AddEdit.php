<?php

namespace Verba\Act;

class AddEdit extends AddEditHandler
{

    public $object_index;

    protected $gettedObjectData = array();
    protected $virtualComponentsData = array();
    protected $ObjectData = array();
    protected $tempData = array();
    protected $actualData = null;

    protected $linked = array();
    protected $unlinked = array();
    public $need2BeLinked = array();

    public $entry_changed = false;
    protected $queryExecuted = false;
    public $cleanCache = array();
    public $ignoreErrors = false;
    protected $delayCreate = false;
    protected $attrs_map;
    public $need2BeRemoved = array();

    protected $virtualHandled = false;
    protected $ObjectDataAsCodes = false;
    protected $ignorInsertErrors = false;

    /**
     * @var bool|string
     */
    protected $responseAs = false;
    protected $responseAsKeys;
    protected $validResponseFormats = array(
        'item',
        'data',
        'data-getted',
        'data-updated',
        'data-keys',
        'iid',
        'bool',
        'json-item-updated',
        'json-item-keys',
        'json-item-updated-keys',
    );
    protected $validResponseDefaultFormat = 'bool';
    /**
     * @var \Verba\Model\Item
     */
    protected $_actualItem;

    function getResponseByFormat($format = null, $keys = null)
    {
        if ($format === null) {
            $format = $this->responseAs;
        }
        if ($keys === null) {
            $keys = $this->responseAsKeys;
        }

        if (!is_string($format) || !in_array($format, $this->validResponseFormats)) {
            $format = $this->validResponseDefaultFormat;
        }

        if ($format == 'item') {

            $r = $this->getActualItem();
        } elseif ($format == 'data') {
            $r = $this->getActualData();
        } elseif ($format == 'data-getted') {
            $data = $this->getActualData();
            $getted = $this->getGettedObjectData();
            $r = array_intersect_key($data, $getted);
        } elseif ($format == 'data-updated') {
            $r = $this->getUpdatedData(false);
        } elseif ($format == 'data-updated-extended') {
            $r = $this->getUpdatedData(true);
        } elseif ($format == 'data-keys') {

            $r = $this->getActualItem()->exportAsValues($keys);

        } elseif ($format == 'iid') {
            $r = $this->getIID();
        } elseif (strpos($format, ($json_item_prefix = 'json-item-')) === 0) {
            $subaction = substr($format, strlen($json_item_prefix));

            // json-item-keys
            if (is_array($keys) && count($keys)) {
                $data = $this->getActualItem()->exportAsValues($keys);
            }
            // json-item-updated
            // json-item-updated-keys
            if (!isset($data) || $subaction == 'updated-keys') {
                if (!isset($data) || !is_array($data)) {
                    $data = array();
                }
                $data = array_replace_recursive($data
                    , $this->getActualItem()->exportAsValues($this->getUpdatedKeys())
                );
            }

            $r = array(
                'iid' => $this->getIID(),
                'ot_id' => $this->oh()->getID(),
                'item' => $data,
            );
        } else {
            $r = (int)((bool)$this->getIID());
        }

        return isset($r) ? $r : false;
    }

    function setResponseAs($val)
    {
        if (!is_string($val) || !$val) {
            $this->responseAs = $this->validResponseDefaultFormat;
            return false;
        }
        $val = strtolower($val);
        if (!in_array($val, $this->validResponseFormats)) {
            return false;
        }
        $this->responseAs = $val;
        return $this->responseAs;
    }

    function setResponseAsKeys($val)
    {
        if (!is_array($val)) {
            return false;
        }
        $this->responseAsKeys = $val;
        return $this->responseAsKeys;
    }


    static $config_default;

    function __construct($cfg)
    { //$action, $ot_id, $object_index = 0, $key_id = false, $iid = false, $p_ot = false, $piid=false){
        $this->initConfigurator(SYS_CONFIGS_DIR.'/ae', 'ae', 'config');
        $this->config = self::$_config_default;

        if (array_key_exists('iid', $cfg)) {
            $this->setIid($cfg['iid']);
            unset($cfg['iid']);
        }

        if (array_key_exists('action', $cfg)) {
            $this->setAction($cfg['action']);
            unset($cfg['action']);
        }
//    if(!$this->action){
//      throw new \Exception('Action is empty');
//    }
        $this->applyConfigDirect($cfg);

        if (!$this->ot_id) {
            throw new \Exception('OType is empty');
        }

        if (!$this->keyId) {
            $this->setKeyId($this->oh->getBaseKey());
        }

        $this->setDelayCreate(count($this->oh->getAttrsByBehaviors('delayCreate')));

        $this->tpl = \Verba\Hive::initTpl();
    }

    public function makeLogAlias()
    {
        $a = get_class($this) . '-' . rand();
        return $a;
    }

    function isUpdated()
    {
        return $this->entry_changed;
    }

    function isQueryExecuted()
    {
        return $this->queryExecuted;
    }

    function checkInputParameters()
    {

        if ($this->action === null) {
            if ($this->iid !== null) {
                $this->action = 'edit';
            } else {
                $this->action = 'new';
            }
        }

        if (!is_array($this->gettedObjectData)) {
            $this->log()->error('Getted Data is empty ' . __METHOD__ . ' (' . __LINE__ . ')] $ot=[' . $this->ot_id . ']');
        }
        if ($this->action == 'edit' && empty($this->iid)) {
            $this->log()->error('Edit error: object ID not found');
        }

        if ($this->action != 'edit' && $this->action != 'new') {
            $this->log()->error('Unknown AE action \'' . $this->action . '\'');
        }
    }

    function defaultTPLs()
    {
        $this->tpl->define([
            'ae_attr_error' => 'ae_attr_error.tpl'
        ]);
    }

    /**
     * @param int|false object index while mass addedit (multimode)
     */
    function setIndex($val)
    {
        $this->object_index = is_numeric($val) && settype($val, 'integer')
            ? $val
            : 0;
    }

    function setIgnoreInsertErrors($val)
    {
        $this->ignorInsertErrors = (bool)$val;
    }

    protected function makeAttrsMap()
    {
        $map = $this->oh->getAttrs(true);
        $attr_alias = $this->gC('attr_aliases');
        if (is_array($attr_alias)) {
            foreach ($attr_alias as $alias => $attr_code) {
                if (!is_object($A = $this->oh->A($attr_code))) continue;
                $map[$A->getID()] = $alias;
            }
        }
        return $map;
    }

    protected function getAttrsMap()
    {
        if ($this->attrs_map === null) {
            $this->attrs_map = $this->makeAttrsMap();
        }

        return $this->attrs_map;
    }

    function setGettedObjectData($getted)
    {
        if (!is_array($getted) || !count($getted)) {
            return false;
        }
        // Извлечение линкующихся объектов
        // из входящих данных
        $this->extractAndAddLinksFromData($getted);

        $attrs_map = $this->getAttrsMap();

        foreach ($getted as $attr_key => $value) {
            $attr_key_original = $attr_key;
            if ((is_numeric($attr_key) && isset($attrs_map[$attr_key]) || false !== ($attr_key = array_search($attr_key, $attrs_map)))
                && is_object($A = $this->oh->A($attr_key))
            ) {
                $this->gettedObjectData[$A->getCode()] = $value;
            } else {
                $this->gettedObjectData[$attr_key_original] = $value;
            }
        }
    }

    function resetGettedData()
    {
        $this->gettedObjectData = array();
    }

    function getGettedObjectData()
    {
        return $this->gettedObjectData;
    }

    function setGettedData($getted)
    {
        return $this->setGettedObjectData($getted);
    }

    function getGettedValue($attr)
    {
        $code = $this->oh->isA($attr)
            ? $this->oh->A($attr)->getCode()
            : $attr;

        return isset($this->gettedObjectData[$code])
            ? $this->gettedObjectData[$code]
            : null;
    }

    function getObjectData()
    {
        return $this->ObjectData;
    }

    function getObjectValue($attr)
    {
        return is_object($a = $this->oh->A($attr)) && isset($this->ObjectData[$a->getCode()])
            ? $this->ObjectData[$a->getCode()]
            : null;
    }

    function getTempValue($attr)
    {
        return is_object($a = $this->oh->A($attr)) && isset($this->tempData[$a->getCode()])
            ? $this->tempData[$a->getCode()]
            : null;
    }

    function getUpdatedData($includeExtended = false)
    {
        $includeExtended = (bool)$includeExtended;
        return $includeExtended
            ? array_merge($this->ObjectData, $this->extendedData)
            : $this->ObjectData;
    }

    function getUpdatedKeys()
    {
        return array_keys($this->ObjectData);
    }

    function getUpdatedValue($key)
    {
        return array_key_exists($key, $this->ObjectData)
            ? $this->ObjectData[$key]
            : null;
    }

    function getActualData()
    {
        if ($this->actualData === null) {
            $this->actualData = array();
            if ($this->iid) {
                $this->actualData = $this->oh->getData($this->iid, 1, true);
            }
        }
        return $this->actualData;
    }

    function setActualItem($Item)
    {

        if (is_array($Item)) {
            $Item = $this->oh->initItem($Item, array('allLangMode' => true));
        }

        if (!$Item instanceof \Verba\Model\Item) {
            return $this->_actualItem;
        }
        $this->_actualItem = $Item;
        return $this->_actualItem;
    }

    function refreshActualItem()
    {
        $this->_actualItem = $this->oh->initItem($this->getIID(), array('allLangMode' => true));
    }

    function getActualItem()
    {
        if ($this->_actualItem === null) {
            $this->refreshActualItem();
        }
        return $this->_actualItem;
    }

    function getActualValue($attr)
    {
        $A = $this->oh->A($attr);
        if (!$A) {
            return null;
        }
        $attr_code = $A->getCode();
        $val = null;

        if ($this->isUpdated()
            && array_key_exists($attr_code, $this->ObjectData)) {
            $val = $this->ObjectData[$attr_code];
        } elseif (array_key_exists($attr_code, $this->tempData)
            && $this->tempData[$attr_code] !== null) {
            $val = $this->tempData[$attr_code];
        }

        if (!isset($val)
            && $this->action == 'edit'
            && array_key_exists($attr_code, $this->exists_values)) {
            $val = $this->exists_values[$attr_code];
        }
        return $val;
    }

    function resetActualData()
    {
        $this->actualData = null;
    }

    function makeObjectKey()
    {
        $object_key = \KeyKeeper::key_assign_base_object($this->ot_id);
        return is_numeric($object_key) && $object_key > 0
            ? $object_key
            : 0;
//    $keys_assign_rules = $this->oh->get_keys_assign_rules();
//
//    if(!(is_array($keys_assign_rules) && count($keys_assign_rules)) ){
//      $this->log()->error('Doesn\'t given Key generation rules. ot_id['.$this->ot_id.']');
//      return false;
//    }
//
//    uasort($keys_assign_rules, 'sort_by_priority');
//    reset($keys_assign_rules);
//
//    foreach($keys_assign_rules as $rule_id => $c_assign_rule){
//      $function_name = "key_assign_{$c_assign_rule['rule']}";
//      if(function_exists($function_name)){
//        $object_key = (int)$function_name($this->ot_id, $this->ObjectData, $this->gettedObjectData);
//      }
//      if(is_numeric($object_key) && $object_key > 0){
//        break;
//      }
//    }
//
//    return is_numeric($object_key) && $object_key > 0
//      ? $object_key
//      : 0;
    }

    function handleAttributesValues($isDelayCreate = false)
    {
        if (!$isDelayCreate) {
            $denied = array();
            if ($this->action == 'edit') $denied[] = 'not_editable';
            if ($this->action == 'new') $denied[] = 'delayCreate';

            $attrs_to_handle = $this->oh->getAttrs(true, false, $denied);

        } elseif ($isDelayCreate == true) {
            $attrs_to_handle = $this->oh->getAttrs(true, 'delayCreate');
        }

        if (!isset($attrs_to_handle)
            || !is_array($attrs_to_handle)
            || empty($attrs_to_handle)) {
            $this->log()->error('Have no attribute to handle');
            return array();
        }

        if (is_array($ordered = $this->gC('fields_handling_order')) && count($ordered)) {
            $ordered = array_reverse($ordered);
            foreach ($ordered as $attr_code) {
                if (false === ($attr_id = array_search($attr_code, $attrs_to_handle))) continue;
                unset($attrs_to_handle[$attr_id]);
                $attrs_to_handle = array($attr_id => $attr_code) + $attrs_to_handle;
            }
        }

        $r = array();
        $locales = \Verba\Lang::getUsedLC();

        foreach ($attrs_to_handle as $attr_id => $attr_code) {
            //$incomingData = $existsData = null;
            $A = $this->oh->A($attr_id);
            // attribute handlers
            $aths = $A->getHandlers('ae');
            $isObligatory = $this->oh->in_behavior('obligatory', $attr_id);

            if (!is_array($aths)) {
                $aths = array();
            }
            array_push($aths, array('ah_name' => $A->getHandlerByType(), 'set_id' => 0));

            $isLcd = $A->isLcd();
            if ($isLcd) {
                $lcs = $locales;
                $incomingData = $this->gettedObjectData[$attr_code];
                $existsData = $this->exists_values[$attr_code];
            } else {
                $lcs = [0];
                $incomingData = [0 => $this->gettedObjectData[$attr_code]];
                $existsData = [0 => $this->exists_values[$attr_code]];
            }

            foreach ($lcs as $lc) {

                $cValue = isset($incomingData[$lc])
                    ? $incomingData[$lc]
                    : null;

                ################
                #              #
                #   Handlers   #
                #              #
                ################

                foreach ($aths as $set_id => $set_data) {
                    if(!is_string($set_data['ah_name']) || !$set_data['ah_name']) {
                        $this->log()->error('Bad attr handler set_id: '.var_export($set_id, true));
                        continue;
                    }

                    if($set_data['ah_name']{0} === '\\'){
                        $handlerClass = $set_data['ah_name'];
                    }else{
                        $handlerClass = '\Verba\Act\AddEdit\Handler\Around\\' . ucfirst($set_data['ah_name']);
                    }

                    if (!class_exists($handlerClass)) {
                        $this->log()->error('['.$this->oh()->getCode().' ('.$this->oh()->getId().'), attr: '.$attr_code.' ('.$attr_id.')] AddEdit attribute handler not found: ' . var_export($handlerClass, true));
                        continue;
                    }
                    /**
                     * @var $handler \Verba\Act\AddEdit\Handler
                     */
                    $handler = new $handlerClass($this->oh, $A,
                        [
                            'A' => $A,
                            'value' => $cValue,
                            'lc' => $lc,
                            'set_data' => $set_data
                        ],
                        $this);
                    try{
                        $cValue = $handler->run();
                    }catch( \Exception $e){
                        $this->log()->error($e->getMessage());
                        $cValue = false;
                        break;
                    }

                }

                if ($this->action == 'new' && $cValue === null && !empty($A->default_value)) {
                    $cValue = $A->default_value;
                }
                if ($isLcd) {
                    if (!$cValue && $this->action == 'new' && $lc !== \Verba\Lang::getDefaultLC()) {
                        $cValue = '';
                    }
                    $this->tempData[$attr_code][$lc] = $cValue;
                } else {
                    $this->tempData[$attr_code] = $cValue;
                }
                // ### Value analizing
                // if field is required and not present while entry is created logs error
                if ($this->action == 'new' && $isObligatory && $cValue !== 0 && !$cValue && ($lc == 0 || $lc === \Verba\Lang::getDefaultLC())) {
                    $this->log()->error(\Verba\Lang::get('aef errors invalid', array(
                        'fieldTitle' => $A->getTitle(),
                        'locale' => ($isLcd ? ' locale:[' . $lc . ']' : '')
                    )));
                    continue;
                }

                if ($cValue !== false && $cValue !== null
                    && (!isset($existsData[$lc])
                        || $cValue != $existsData[$lc])
                ) {
                    if ($isLcd) {
                        $r[$attr_code][$lc] = $cValue;
                    } else {
                        $r[$attr_code] = $cValue;
                    }
                }
            }
        }
        return count($r) > 0 ? $r : array();
    }

    function makePrimaryObject()
    {
        $iid = $this->execAEQuery($this->ObjectData);

        if (!is_numeric($iid) && !is_string($iid))
            return false;

        $this->entry_changed = true;
        if ($this->action == 'new')
            $this->iid = $iid;

        //донаполнение массива ObjectData
        $this->ObjectData[$this->oh->getPAC()] = $iid;
        $this->ObjectData['ot_id'] = $this->oh->getID();
        $this->ObjectData['ok'] = $this->keyId;
        return true;
    }

    function execAEQuery($ObjectData, $delayCreate = false)
    {
        $fields = array();
        //Создание инсерта в первичный ваулт.
        foreach ($ObjectData as $attr_code => $attr_value) {
            if ($this->oh->A($attr_code)->get_lcd() && is_array($attr_value) && count($attr_value)) {

                foreach ($attr_value as $c_lc => $c_lc_value) {
                    if (\Verba\Lang::isLCValid($c_lc)) {
                        $fields[$attr_code . '_' . $c_lc] = $this->DB()->escape_string($c_lc_value);
                    }
                }
            } else {
                $fields[$attr_code] = $this->DB()->escape_string($attr_value);
            }
        }

        if ($this->action == 'new' && !$delayCreate) {

            $query = "INSERT " . (!$this->ignorInsertErrors ? '' : 'IGNORE') . " INTO {$this->oh->vltURI()} (`ot_id`, `key_id`, `" . implode('`, `', array_keys($fields)) . "`) VALUES ('{$this->oh->getID()}', '{$this->keyId}', '" . implode("', '", $fields) . "')";

        } elseif ($this->action == 'edit' || ($this->action == 'new' && $delayCreate)) {
            $fields_str = '';
            foreach ($fields as $k => $v) {
                $fields_str .= ", `$k` = '$v'";
            }
            $fields_str = mb_substr($fields_str, 1);

            $idField = \Verba\QueryMaker::getIdFieldNameByIdValue($this->oh, $this->iid);

            $query = "UPDATE {$this->oh->vltURI()} SET $fields_str WHERE `ot_id` = '{$this->oh->getID()}' && `$idField` = '{$this->iid}'";
        }

        $iid = false;

        if ($sqlr = $this->DB()->query($query)) {
            $this->queryExecuted = true;
            if ($this->getAction() == 'new' && !$delayCreate) {
                $iid = $sqlr->getInsertId();
            } elseif ($sqlr->getAffectedRows()) {
                $iid = $this->iid;
            }
        }
        return (is_numeric($iid) || is_string($iid)) && !empty($iid) ? $iid : false;
    }

    function doDelayCreate()
    {
        $oh = \Verba\_oh($this->ot_id);

        $ObjectData = $this->handleAttributesValues(true);
        if (count($ObjectData)) {
            $iid = $this->execAEQuery($ObjectData, true);
        }

        return $ObjectData;
    }

    function isDelayCreate()
    {
        return $this->delayCreate;
    }

    function setDelayCreate($val)
    {
        return $this->delayCreate = (bool)$val;
    }

    function addToUnlink($ot, $iids = false, $linkProps = false)
    {
        return $this->addLinkAction($ot, $iids, $linkProps, 'Removed');
    }

    function addToLink($ot, $iids = false, $linkProps = false)
    {
        return $this->addLinkAction($ot, $iids, $linkProps, 'Linked');
    }

    protected function addLinkAction($ot, $iids = false, $linkProps = array(), $action = 'Linked')
    {
        $_oh = \Verba\_oh($ot);
        if (!is_object($_oh)) {
            return false;
        }
        $ot = $_oh->getID();
        $prop = 'need2Be' . $action;

        if (!is_array($linkProps)) {
            $linkProps = (array)$linkProps;
        }
        $linkProps['prim_ot_id'] = $this->oh()->getID();
        $linkProps['sec_ot_id'] = $ot;

        $linkProps = new \Verba\ObjectType\LinkProps($linkProps);

        $linkGid = $linkProps->gid;

        if (!isset($this->{$prop}[$ot])) {
            $this->{$prop}[$ot] = array();
        }
        if (!isset($this->{$prop}[$ot][$linkGid])) {
            $this->{$prop}[$ot][$linkGid] = array(
                'lp' => $linkProps,
                'items' => array(),
            );
        }

        $this->{$prop}[$ot][$linkGid]['items'] = array_merge($this->{$prop}[$ot][$linkGid]['items'], (array)$iids);
    }

    function getFromToLink($ot, $linkProps = array())
    {
        return $this->getFromLinkable($ot, $linkProps, 'Linked');
    }

    function getFromToLinkFirstIid($ot, $linkProps = array())
    {
        $r = $this->getFromToLink($ot, $linkProps = array());
        if (is_array($r) && count($r)) {
            reset($r);
            return current($r);
        }
        return false;
    }

    function getFromToUnlink($ot, $linkProps = array())
    {
        return $this->getFromLinkable($ot, $linkProps, 'Removed');
    }

    function getFromToUnlinkFirstIid($ot, $linkProps = array())
    {
        $r = $this->getFromToUnlink($ot, $linkProps);
        if (is_array($r) && count($r)) {
            reset($r);
            return current($r);
        }
        return false;
    }

    function getFromLinkable($ot, $linkProps, $action = 'Linked')
    {
        $_oh = \Verba\_oh($ot);
        if (!is_object($_oh)) {
            return false;
        }
        $ot = $_oh->getID();
        $prop = 'need2Be' . $action;
        if (!is_array($linkProps)) {
            $linkProps = (array)$linkProps;
        }
        $linkProps['prim_ot_id'] = $this->oh()->getID();
        $linkProps['sec_ot_id'] = $ot;
        $linkProps = new \Verba\ObjectType\LinkProps($linkProps);

        $linkGid = $linkProps->gid;

        if (!isset($this->{$prop}[$ot][$linkGid]['items'])) {
            return null;
        }
        if (!is_array($this->{$prop}[$ot][$linkGid]['items'])) {
            return false;
        }
        return $this->{$prop}[$ot][$linkGid]['items'];
    }

    function updateLinks()
    {
        //Cвязи с заявленным родителем.
        if (count($this->parents)) {
            $linkProps = $this->getGettedValue('_link_props');
            foreach ($this->parents as $parentOt => $parentIids) {
                $_p = \Verba\_oh($parentOt);
                $p_ot_id = $_p->getID();
                $lp = new \Verba\ObjectType\LinkProps(array(
                    'prim_ot_id' => $this->oh()->getID(),
                    'sec_ot_id' => $p_ot_id,
                    'fr' => 2,
                ));

                if (isset($linkProps[$p_ot_id][$lp->gid]['extData'])) {
                    $lp->extData = $linkProps[$p_ot_id][$lp->gid]['extData'];
                }
                $this->addToLink($parentOt, $parentIids, $lp->asCfg());
            }
        }
        // Извлечение линкующихся объектов
        // из входящих данных если те были
        // добавлены напрямую в массив
        $this->extractAndAddLinksFromData($this->gettedObjectData);

        // Линкование
        $unlinked = $this->doUnlinks();
        $linked = $this->doLinks();

        //Запись в лог
        if ($unlinked > 0 || $linked > 0) {
            $this->log()->event('AE ' . $this->oh->getCode() . '-' . $this->iid . ' linked: ' . $linked . ", unlinked: " . $unlinked);
        }
    }

    function extractAndAddLinksFromData(&$data)
    {
        foreach (array('_unlink', '_link') as $action) {
            if (!isset($data[$action])) {
                continue;
            }
            if (!is_array($data[$action])) {
                unset($data[$action]);
                continue;
            }
            $mtd = $action == '_unlink' ? 'addToUnlink' : 'addToLink';
            foreach ($data[$action] as $ot => $links) {
                if (!is_array($links)) {
                    continue;
                }
                foreach ($links as $linkGid => $iids) {
                    if (isset($data['_link_props'][$ot][$linkGid])
                        && is_array($data['_link_props'][$ot][$linkGid])) {
                        $linkProps = new \Verba\ObjectType\LinkProps($data['_link_props'][$ot][$linkGid]);
                        $rule = $linkProps->rule;
                        $fr = $linkProps->fr;
                    } else {
                        list($pot, $sot, $rule, $fr) = explode('-', $linkGid);
                    }

                    $this->$mtd($ot, $iids, array('rule' => $rule, 'fr' => $fr));
                }
            }

            unset($data[$action]);
        }
    }

    function getFirstParentOt(){
        $r = parent::getFirstParentOt();
        if($r) {
            return $r;
        }

        if(count($this->linked['p'])){
            reset($this->linked['p']);
            return key($this->linked['p']);
        }
        return false;
    }

    function getFirstParentIid(){
        $r = parent::getFirstParentIid();
        if($r) {
            return $r;
        }

        if(count($this->linked['p']))
        {
            reset($this->linked['p']);
            $ot = key($this->linked['p']);
            reset($this->linked['p'][$ot]);
            $ruleAlias = key($this->linked['p'][$ot]);
            return current($this->linked['p'][$ot][$ruleAlias]);
        }
        return false;
    }

    function doUnlinks()
    {
        $this->fire('beforeUnlink');
        $r = 0;
        if (count($this->need2BeRemoved)) {
            foreach ($this->need2BeRemoved as $ot => $links) {
                foreach ($links as $linkGid => $linkData) {
                    /**
                     * @var $lp \Verba\ObjectType\LinkProps
                     */
                    $lp = $linkData['lp'];
                    list($aff, $log) = $this->oh->unlink($this->iid, array($ot => $linkData['items']), $lp->getRule(), $lp->getFr());
                    $this->unlinked = array_replace_recursive($this->unlinked, $log);
                    $r += $aff;
                }
            }
        }

        $this->fire('afterUnlink');
        return $r;
    }

    function doLinks()
    {
        $this->fire('beforeLink');
        $r = 0;
        if (count($this->need2BeLinked)) {
            foreach ($this->need2BeLinked as $ot => $links) {
                foreach ($links as $linkGid => $linkData) {
                    /**
                     * @var $lp \Verba\ObjectType\LinkProps
                     */
                    $lp = $linkData['lp'];
                    list($aff, $log) = $this->oh->link($this->iid, array($ot => $linkData['items']), $lp->getRule(), $lp->getFr(), $lp->getExtData());
                    $this->linked = array_replace_recursive($this->linked, $log);
                    $r += $aff;
                }
            }
        }
        $this->fire('afterLink');
        return $r;
    }

    function getLinked()
    {
        return $this->linked;
    }

    function getUnlinked()
    {
        return $this->unlinked;
    }

    function getVirtualComponent($key)
    {
        return isset($this->virtualComponentsData[$key]) ? $this->virtualComponentsData[$key] : false;
    }

    function haveErrors()
    {
        return $this->log()->countMessages('error') > 0 ? true : false;
    }

    function initHandler($className, $cfg)
    {
        $h = new $className($this, $cfg);
        if (!$h->isAllowed($this->action)) {
            return false;
        }
        return $h;
    }

    function runHandlers($case)
    {

        if (!in_array($case, array('after', 'before'))) {
            return false;
        }

        $A = $this->oh->A($this->oh->getPAC());
        $aths = $A->getHandlers('ae_' . $case);
        if (!is_array($aths) || empty($aths)) {
            return null;
        }

        foreach ($aths as $set_id => $set_data) {
            if(is_string($set_data['ah_name']) && $set_data['ah_name']{0} === '\\'){
                $className = $set_data['ah_name'];
            }else{
                $className = '\Verba\Act\AddEdit\Handler\\' . ucfirst($case). '\\' .ucfirst($set_data['ah_name']);
            }

            if (!class_exists($className)) {
                $this->log()->error('Bad AE Handler `' . var_export($className, true) . '` class file not found.');
                continue;
            }

            $cfg = isset($set_data['params']) && !empty($set_data['params']) ? $set_data['params'] : false;
            /**
             * @var $h \Verba\Act\AddEdit\Handler
             */
            $h = new $className($this->oh, $A, $cfg, $this);
            if (!$h->isAllowed($this->action)) {
                continue;
            }

            $h->run();
        }

        return true;
    }

    function runHandlersAfter()
    {
        $this->runHandlers('after');
    }

    function runHandlersBefore()
    {
        $this->runHandlers('before');
    }

    function setIgnoreErrors($val)
    {
        $this->ignoreErrors = (bool)$val;
    }

    function getIgnoreErrors()
    {
        return $this->ignoreErrors;
    }

    // *** Action ***
    function addedit_object()
    {

        $this->checkInputParameters();
        if ($this->ignoreErrors === false && $this->log()->countMessages('error') > 0) {
            throw new \Exception($this->log()->getMessagesAsStr('error'));
        }

        $this->defaultTPLs();

        // Получение существующих значений изменяемой записи.
        if ($this->action == 'edit' && !$this->isExistsValuesLoaded()) {
            $this->loadExistsValues();
        }

        $this->runHandlersBefore();

        // Получение и обработка значений атрибутов для помещения в хранилище.
        $this->ObjectData = $this->handleAttributesValues();

        //Generate object key
        if (!$this->keyId) {
            $this->keyId = $this->makeObjectKey();
            if (!$this->keyId) {
                $this->log->error('Unknown object key ??');
            }
        }

        $this->gettedObjectData['ok'] = $this->keyId;


        if ($this->ignoreErrors === false && $this->log()->countMessages('error')) {
            $this->log()->error("AE \Exception. Dumping Data:\n\ngettedObjectData:" . var_export($this->gettedObjectData, true) . "\n\n$_REQUEST:" . var_export($_REQUEST, true), 0, 1);
            throw new \Exception($this->log()->getMessagesAsStr('error'));
        }

        // +++ Object
        if (count($this->ObjectData)) {
            //Create main object entry
            $this->makePrimaryObject();
            if (!$this->iid) {
                throw new \Exception('Unable to \'' . $this->getAction() . '\' entry. Action failed.');
            }

            //delayCreate
            if ($this->getAction() == 'new' && is_numeric($this->iid) && $this->isDelayCreate()) {
                $delayData = $this->doDelayCreate();
                if (is_array($delayData)) {
                    $this->ObjectData = array_replace_recursive($this->ObjectData, $delayData);
                }
            }
        }

        // Linking
        $this->updateLinks();
        $this->fire('beforeComplete');

        $this->runHandlersAfter();
        return $this->iid;
    }
}

AddEdit::$_config_default = array(
    'fields_handling_order' => false, // последовательность обработки значений атрибутов. формат array(attr1_code, attr2_code, ...)
    'attr_aliases' => false,          // алиасы атрибутов для gettedValues формат array(alias1 => attr1_code, ...)
);
