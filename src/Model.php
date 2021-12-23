<?php

namespace Verba;

class Model extends Base implements \Verba\ModelInterface
{

    public $ot_id;
    public $code;

    /**
     * @var ObjectType
     */
    public $OT;
    public $_base;
    public $baseId;

    protected static $directionValues = [
        'up' => 2,
        'down' => 1,
        'both' => 3
    ];
    protected $OTIC;

    function __construct($ot_id, $base_ot = false)
    {
        $this->OT = new ObjectType($this, $ot_id, $base_ot);

        if ($base_ot) {
            $this->_base = \Verba\_oh($base_ot);
            $this->baseId = $this->_base->getID();
        }

        $this->ot_id = $this->OT->id;
        $this->code = $this->OT->code;
    }

    function __call($method, $args)
    {
        $meths = get_class_methods($this->OT);
        if (!in_array($method, $meths)) {
            throw new Exception('Undefined class method called: ' . __CLASS__ . ':' . $method . '()');
        }

        return call_user_func_array(array($this->OT, $method), $args);
    }

    function __sleep()
    {
        return array('ot_id', 'code', 'OT');
    }

    function oh(){
        return $this;
    }

    function getDescendants($all = true)
    {
        global $S;

        $all = (bool)$all;

        return $all ? $S->getOtDescendants($this->ot_id) : $S->getOtDescendantsDirect($this->ot_id);
    }

    function getAncestors()
    {
        return $this->OT->getAncestors();
    }

    function getBaseId()
    {
        return $this->baseId;
    }

    /**
     * Возвращает объект класса ObjectType для $this->ot_id
     * @return object ObjectType
     * @see ObjectType
     *
     */
    public function getOT()
    {
        if (is_object($this->OT)) {
            return $this->OT;
        } elseif (is_object($this->_base)) {
            return $this->_base->getOT();
        }
        return null;
    }

    public function getPAC()
    {
        if ($this->OT->prim_attr_code) {
            return $this->OT->prim_attr_code;
        } elseif (is_object($this->_base)) {
            return $this->_base->getPAC();
        }
        return null;
    }

    public function getPAID()
    {
        if ($this->OT->prim_attr_id) {
            return $this->OT->prim_attr_id;
        } elseif (is_object($this->_base)) {
            return $this->_base->getPAID();
        }
        return null;
    }

    public function getStringPAID()
    {
        if ($this->OT->stringPAID) {
            return $this->OT->stringPAID;
        } elseif (is_object($this->_base)) {
            return $this->_base->getStringPAID();
        }
        return null;
    }

    public function getStringPAC()
    {
        if ($this->OT->stringPAC) {
            return $this->OT->stringPAC;
        } elseif (is_object($this->_base)) {
            return $this->_base->getStringPAC();
        }
        return null;
    }

    // vault id
    public function get_vlt()
    {
        if ($this->OT->vlt_id) {
            return $this->OT->vlt_id;
        } elseif (is_object($this->_base)) {
            return $this->_base->get_vlt();
        }
        return null;
    }

    public function getID()
    {
        return $this->ot_id;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getRole()
    {
        return $this->OT->role;
    }

    public function getBaseKey()
    {
        if ($this->OT->base_key) {
            return $this->OT->base_key;
        } elseif (is_object($this->_base)) {
            return $this->_base->getBaseKey();
        }
        return null;
    }

    public function getOwnerAttributeCode()
    {
        return $this->OT->ownerAttributeId
            ? $this->OT->id2code($this->OT->ownerAttributeId)
            : ($this->OT->is_attribute('owner')
                ? 'owner'
                : false
            );
    }

    public function getOwnerAttributeId()
    {
        return $this->OT->ownerAttributeId
            ? $this->OT->ownerAttributeId
            : $this->OT->code2id('owner');
    }

    function getTitle()
    {
        return $this->OT->display;
    }

    function getObjectsData($key_id, $iids = false, $attr_list = true, $all_langs = false, $limit = false, $offset = false, $selectPdv = true)
    {
        $limit = is_numeric($limit) && ($limit = intval($limit)) > 0 ? $limit : false;
        $qm = new QueryMaker($this->getID(), $key_id, $attr_list, $all_langs, $selectPdv);

        $qm->addWhereIids($iids);

        if ($limit) {
            $qm->addLimit($limit);
        }
        $qm->addWhere($this->getID(), 'ot_id');

        $q = $qm->getQuery();
        return $this->DB()->query($q);
    }

    /**
     * Возвращает данные по переданным iid для объектов текущего ОТ
     * @param int|array $iids число или массив id данные по которым надо получить.
     * @param int|bool $count режим возврата данных. true - всегда массивом объектов; false|1 - только первый объект; null - автовыбор - если в результате одна строка - одним объектом, если более - массивом объектов.
     * @param string|array $attr_list перечень атрибутов которые надо извлечь.
     * @param integer $key_id ключ доступа
     * @param boolean $all_langs значения для всех мультиязычных полей
     * @param boolean $selectPdv извлекать дополнительно или нет значения для predefined полей?
     * @param boolean $lcdAttrsAsArray если true - заменить значения локалезависимых полей как массив [field_en] => [field][en]
     *
     * @return array|false массив данных.
     *
     * @see ObjectType::getAttrs() как пример возможных $attr_list
     */
    function getData($iids = false, $count = null, $attr_list = true, $key_id = false, $all_langs = false, $selectPdv = true, $lcdAttrsAsArray = false)
    {
        $lcdAttrsAsArray = (bool)$lcdAttrsAsArray;
        $count = (is_numeric($count) && ($count = intval($count)) > 0) || ($count === false && $count = 1)
            ? $count
            : (is_bool($count) || $count === null ? $count : null);

        $sqlR = $this->getObjectsData(
            !is_numeric($key_id) ? $this->getBaseKey() : intval($key_id),
            $iids,
            $attr_list,
            $all_langs,
            is_int($count) ? $count : false, false, $selectPdv);

        if (!is_object($sqlR) || $sqlR->getNumRows() == 0) {
            return false;
        }

        $result = array();

        if (($count === null && $sqlR->getNumRows() == 1) || $count === 1) {
            $row = $sqlR->fetchRow();
            return $lcdAttrsAsArray
                ? $this->substrLcdAttrAsArrayInData($row)
                : $row;
        } else {
            while ($row = $sqlR->fetchRow()) {
                $result[$row[$this->getPAC()]] = $lcdAttrsAsArray
                    ? $this->substrLcdAttrAsArrayInData($row)
                    : $row;
            }
        }
        return $result;
    }

    function substrLcdAttrAsArrayInData($item, $lcdAttrs = false)
    {
        if (!$lcdAttrs) {
            $lcdAttrs = $this->getAttrsByBehaviors('lcd');
        }
        if (!is_array($lcdAttrs) || !count($lcdAttrs)
            || !is_array($item) || !count($item)) {
            return $item;
        }

        foreach ($lcdAttrs as $attr_id => $attr_code) {
            Lang::substPlaneLcdAttrByLcArray($item, $attr_code);
        }
        return $item;
    }

    /**
     * @return \Verba\Act\AddEdit
     */
    function initAddEdit($cfg = false)
    {//$action, $ot_id = false, $object_index = 0, $key_id = false, $iid = false, $p_ot = false, $piid=false){
        if (is_string($cfg) && !empty($cfg)) {
            $cfg = array('action' => $cfg);
        }

        if (!is_array($cfg)) {
            $cfg = array();
        }
        $cfg['ot_id'] = $this->getID();

        return new \Verba\Act\AddEdit($cfg);
    }

    function initForm($cfg)
    {
        \Verba\Hive::loadFormMakerClass();
        $cfg['ot_id'] = $this->getID();
        $aef = new \Verba\Act\Form($cfg);
        return $aef;
    }

    function initDelete($processId = false)
    {
        $dh = new \Verba\Act\Delete($processId);
        $dh->setOtId($this->getID());
        return $dh;
    }

    /**
     * Инициализация объекта списка.
     * @param array $cfg конфиг для обработчика действия
     *
     * @return \Verba\Act\MakeList объект списка.
     */
    function initList($cfg)
    {
        if (!is_array($cfg)) {
            $cfg = array();
        }

        $cfg['ot_id'] = $this->getID();
        $list = new \Verba\Act\MakeList($cfg);

        return $list;
    }

    /*
  public function getLinksRules(){
    return $this->OT->links_rules;
  }

  function getLinksDeleteRules($ot_id = false){

    if(!is_array($this->OT->links_rules_delete)
      || empty($this->OT->links_rules_delete)) {
      return false;
    }

    if(is_numeric($ot_id)
      && array_key_exists($ot_id, $this->OT->links_rules_delete)){
      return $this->OT->links_rules_delete[$ot_id];
    }elseif($ot_id == false){
      return $this->OT->links_rules_delete;
    }

    return false;
  }
*/
    function getLinksOnlyDeleteOts($own = false)
    {
        $r = array();
        if ($this->_base && !$own) {
            $ots_from_base = $this->_base->getLinksOnlyDeleteOts();
            if (is_array($ots_from_base)) {
                $r = $ots_from_base;
            }
        }

        if (empty($this->OT->links_rules_delete)) {
            return $r;
        }

        foreach ($this->OT->links_rules_delete as $c_del_ot_id => $cr) {
            if ($cr['links_only']) {
                $r[] = $c_del_ot_id;
            }
        }

        return array_unique($r);
    }

    protected function getLocalRule($ot_id, $alias = false)
    {
        $rule = false;
        // Проверяем, есть ли вообще с таким ОТ связи
        if (array_key_exists($ot_id, $this->OT->links_rules)
            && is_array($this->OT->links_rules[$ot_id])) {
            //если есть алиас
            if ($alias) {
                // если прям такое правило существует - возвращаем его
                if (array_key_exists($alias, $this->OT->links_alias)) {

                    $rule = $this->OT->links_alias[$alias];

                    // если
                } elseif (array_key_exists('*', $this->OT->links_alias)) {
                    $rule = $this->OT->links_alias['*'];
                    $rule['alias'] = $alias;
                }


            } elseif (!$alias && count($this->OT->links_rules[$ot_id])) {
                // берется первое в из правил связи
                reset($this->OT->links_rules[$ot_id]);
                foreach ($this->OT->links_rules[$ot_id] as $rid => $ruleData) {
                    if ($ruleData['alias'] == false) {
                        $rule = $ruleData;
                        break;
                    }
                }

            }
        }
        return $rule;
    }

    /**
     * Возвращает информацию о правиле связи с известным типом объектов
     *
     * @param mixed $ot_id ОТ правило с которым возвращается
     * @param string $alias псевдоним правила
     * @param bool $invertedTry внутренний флаг, для реализации взаимного поиска связи у $ot_id
     * @param bool|\Model $originalOType при рекурсивном поиске по родителям, в этом параметре передается оригинальный primOt
     * @return bool|array содержащий ключи id - id правила;  data - массив данных по правилу; type - код правила
     *
     */
    function getRule($ot_id, $alias = false, $invertedTry = false, $originalOType = false, $level = 0)
    {

        $invertedTry = (bool)$invertedTry;
        $originalOTypeInput = false;

        $_sec = \Verba\_oh($ot_id);
        $ot_id = $_sec->getID();

        $rule = $this->getLocalRule($ot_id, $alias);

        if ($rule) {
            goto prepare_result;
        }

        // Когда secOt является расширением от primOt
        // и поиск ведется среди ОТ одного типа,

        if ($_sec->_base && $_sec->_base->getID() == $this->ot_id) {
            $searchOtId = $this->ot_id;
            $rule = $this->getLocalRule($searchOtId, $alias);
        }
        if ($rule) {
            goto prepare_result;
        }

        // Если есть базовый ОТ, пробуем найти в нем
        if ($this->_base) {
            // переопределение искомого ОТ
            // если ситуация поиска среди однотипных записей
            if ($ot_id == $this->ot_id) {
                $searchOtId = $this->_base->getID();
                //иначе - сохранение исходного ot_id
            } else {
                $searchOtId = $ot_id;
            }
            if (!$originalOType) {
                $originalOType = $this;
                $originalOTypeInput = true;
            }
            $rule = $this->_base->getRule($searchOtId, $alias, $invertedTry, $originalOType, $level + 1);
        }

        // Если правило не найдено, попытка найти ответное правило в secOt
        if (!$rule && !$invertedTry) {
            $invertedTryInput = true;
            $rule = $_sec->getRule($this->ot_id, $alias, true, $originalOType, $level + 1);
        }

        if (!$rule) {
            return false;
        }

        prepare_result:

        // если текущий контекст это оригинал
        if ($originalOTypeInput) {
            // поправляем $rule['sec'] если правило найдено у предков
            // подставляем ID $originalOType
            if (in_array($rule['sec'], $originalOType->getAncestors())) {
                $rule['sec'] = $originalOType->getID();
            }
        }

        if ($level == 0) {
            // load linked vault if still not loaded
            if ($rule['rule'] === 'fid') {
                if ($rule['db'] === -1) {
                    $_vltRef = empty($rule['table'])
                        ? \Verba\_oh($rule['sec'])
                        : \Verba\_oh($rule['table']);
                    $rule['db'] = $_vltRef->vltDB();
                    $rule['table'] = $_vltRef->vltT();
                    $rule['uri'] = $_vltRef->vltURI();
                }
            }
        }

        return $rule;
    }

    /**
     * Зменяет в массиве данных цифровые
     * ключи на коды атрибутов либо наоборот - строковые на ID атрибутов.
     *
     * @param mixed $data массив, где все ключи являются или цифровые ID атрибутов либо строковые коды.
     * @param mixed $return режим результата: true - возврат измененного массива; false - замена по ссылке переданного $data
     * @param mixed $invert если true - замена Id=> Code иначе - Code=>Id
     *
     * @return void|array в зависимости от $return
     */
    function substAttrIdsToCodes(&$data, $return = false, $unsetUnknown = true)
    {
        return $this->substAttrsKeys($data, $return, $unsetUnknown);
    }

    function substAttrCodesToIds(&$data, $return = false, $unsetUnknown = true)
    {
        return $this->substAttrsKeys($data, $return, $unsetUnknown, true);
    }

    function substAttrsKeys(&$data, $return = false, $unsetUnknown = true, $invert = false)
    {
        if (!is_array($data) || count($data) < 1) return null;

        $return = (bool)$return;
        $unsetUnknown = (bool)$unsetUnknown;
        $invertor = $invert ? 'getID' : 'getCode';

        $r = array();
        foreach ($data as $key => $value) {
            if (is_object($attr = $this->A($key))) {
                $_key = $attr->$invertor();
            } elseif (!$unsetUnknown) {
                $_key = $key;
            } else {
                continue;
            }
            $r[$_key] = &$data[$key];
        }

        if ($return) {
            return $r;
        } else {
            $data = $r;
        }
        return null;
    }
    /*
  function getLoadedVaultsKeys(){
    return is_array($this->OT->vaults[$this->getID()])
            ? array_keys($this->OT->vaults[$this->getID()])
            : array();
  }*/
    /**
     * @param mixed $key_id
     *
     * @return bool|\Verba\ObjectType\DataVault Объект с данными по хранилищу
     */
    function getVault($key_id = 0)
    {
        $key_id = settype($key_id, 'int') && isset($this->OT->vaults[$key_id]) ? $key_id : 0;

        if (isset($this->OT->vaults[$key_id])) {
            return $this->OT->vaults[$key_id];
        } elseif ($this->_base) {
            return $this->_base->getVault($key_id);
        }

        return false;
    }

    /**
     * Возвращает название таблицы хранения ОТ. Если передан  bundleOtId - будет возвращено название таблицы связей между ними (только по первому правилу.)
     * @param int $bundleOtId OT-id связанного объекта название таблицы связей с которым надо получить.
     * @param int $key_id id ключа в случае если реализовано хранение объектов по ключам
     * @return string название таблицы БД
     */
    function vltT($bundleOtId = false, $key_id = false)
    {
        if (!$bundleOtId) {
            return $this->getVault($key_id)->getObject();
        }

        $bundleOtId = \Verba\_oh($bundleOtId)->getID();
        $rule = $this->getRule($bundleOtId);

        return is_array($rule) && array_key_exists('table', $rule)
            ? $rule['table']
            : false;
    }

    /**
     * Возвращает название БД хранения ОТ. Если передан  bundleOtId - будет возвращено название БД связей между ними (только по первому правилу.)
     * @param int $bundleOtId OT-id связанного объекта, название БД где расположена таблица связей с которым надо получить.
     * @param int $key_id id ключа в случае если реализовано хранение объектов по ключам. По умолчанию - 0.
     * @return string название БД
     */

    function vltDB($bundleOtId = false, $key_id = false)
    {
        if (!$bundleOtId) {
            return $this->getVault($key_id)->getRoot();
        }

        $bundleOtId = \Verba\_oh($bundleOtId)->getID();
        $rule = $this->getRule($bundleOtId);

        return is_array($rule) && array_key_exists('db', $rule)
            ? $rule['db']
            : false;
    }

    /**
     * Возвращает полный SQL-путь к таблице хранения ОТ. Если передан  bundleOtId - будет возвращено для таблицы связей между ними (только по первому правилу.)
     * @param int $bundleOtId OT-id связанного объекта.
     * @param int $key_id id ключа в случае если реализовано хранение объектов по ключам. По умолчанию - 0.
     * @return string SQL-путь формата `dbName`.`tableName`
     */

    function vltURI($bundleOtId = false, $key_id = false)
    {
        if (!$bundleOtId) {
            return $this->getVault($key_id)->getURI();
        }
        $bundleOtId = \Verba\_oh($bundleOtId)->getID();
        $rule = $this->getRule($bundleOtId);

        return is_array($rule) && array_key_exists('uri', $rule)
            ? $rule['uri']
            : false;
    }

    /**
     * Возвращает объект атрибута
     * @param mixed $attr_id код или id атрибута
     * @return bool|\ObjectType\Attribute
     * @see \ObjectType\Attribute
     */
    function A($attr, $own = false)
    {
        return $this->OT->A($attr, $own);
    }

    function isA($needle, $own = false)
    {
        return $this->OT->isA($needle, $own);
    }

    function getAttrs($attr_list = false, $allowed_behaviors = false, $denied_behaviors = false, $rights = false)
    {
        return call_user_func_array(array($this->OT, 'getAttrs'), func_get_args());
    }

    function add_behavior_group($attrs_list, $behavior)
    {
        if (!array_key_exists($behavior, $this->OT->behaviors) || !count($this->OT->behaviors[$behavior]) || !\Verba\reductionToArray($attrs_list)) {
            return $attrs_list;
        }

        return ($attrs_list + $this->OT->behaviors[$behavior]);
    }

    function remove_behavior_group($attrs_list, $behavior)
    {
        if (!is_array($attrs_list))
            $attrs_list = array();

        return array_diff($attrs_list, (is_array($this->OT->behaviors[$behavior]) ? $this->OT->behaviors[$behavior] : array()));
    }

    function in_behavior($behavior, $attr_id)
    {
        if (array_key_exists($behavior, $this->OT->behaviors)
            && is_array($this->OT->behaviors[$behavior])
            && array_key_exists($attr_id, $this->OT->behaviors[$behavior])) {
            return true;
        }

        if ($this->_base) {
            return $this->_base->in_behavior($behavior, $attr_id);
        }

        return false;
    }

    /**
     * Возвращает массив детей или родителей, в зависимости от указанного направления $direction.
     *
     * @param mixed $direction up или down. Если передано иное - по обеим направлениям
     *
     * @return array
     */
    function getFamilyOTs($direction = false, $own = false)
    {
        $r = array();

        if (!$own && $this->_base) {
            $r = $this->_base->getFamilyOTs($direction, $own);
        }

        if (!is_array($this->OT->family) || empty($this->OT->family)) {
            return $r;
        }

        $groups = array('parents', 'childs');
        switch ($direction) {
            case 'up':
                unset($groups[1]);
                break;
            case 'down':
                unset($groups[0]);
                break;
        }

        foreach ($groups as $cg) {
            if (!isset($this->OT->family[$cg]) || !is_array($this->OT->family[$cg])) {
                continue;
            }
            $r = array_merge($r, array_keys($this->OT->family[$cg]));
        }
        return array_unique($r);
    }

    function getFamilyParents($own = false)
    {
        $r = array();

        if (!$own && $this->_base) {
            $r = $this->_base->getFamilyParents($own);
        }
        if (!is_array($this->OT->family['parents']) || !count($this->OT->family['parents'])) {
            return $r;
        }
        $r = array_replace_recursive($r, $this->OT->family['parents']);
        return $r;
    }

    function getFamilyChilds($own = false)
    {
        $r = array();

        if (!$own && $this->_base) {
            $r = $this->_base->getFamilyParents($own);
        }
        if (!is_array($this->OT->family['childs']) || !count($this->OT->family['childs'])) {
            return $r;
        }
        $r = array_replace_recursive($r, $this->OT->family['childs']);
        return $r;
    }

    function inChilds($ot, $own = false)
    {
        $ot = \Verba\_oh($ot);
        if (isset($this->OT->family['childs'][$ot->getID()])) {
            return true;
        }

        if (!$own && $this->_base) {
            return $this->_base->inChilds($ot, $own);
        }

        return false;
    }

    function inParents($ot, $own = false)
    {
        $ot = \Verba\_oh($ot);
        if (isset($this->OT->family['parents'][$ot->getID()])) {
            return true;
        }

        if (!$own && $this->_base) {
            return $this->_base->inParents($ot, $own);
        }

        return false;
    }

    function inFamily($ot)
    {
        return
            array_key_exists($ot, $this->OT->family['parents'])
            || array_key_exists($ot, $this->OT->family['childs'])
                ? true
                : false;
    }

    /**
     * Показывает как $ot_id2 относиться к текущему ОТ
     * @param mixed $ot_id2
     * @return bool|integer
     * 3 - ot2 can be as parent and as child;
     * 2 - ot2 is a parent (current is child);
     * 1 - ot2 is a child (current is parent);
     */
    function getFamilyRelations($secOt, $secondTry = false)
    {
        $_sec = \Verba\_oh($secOt);
        $secOt = $_sec->getID();
        $secondTry = (bool)$secondTry;
        $in_ch = array_key_exists($secOt, $this->OT->family['childs']);
        $in_p = array_key_exists($secOt, $this->OT->family['parents']);

        // если найдено в связях текущего ОТ - возврат результата
        if ($in_ch && $in_p) return 3;
        if ($in_p) return 2;
        if ($in_ch) return 1;

        $fr = false;

        // if current OT is not extends current OT (linked with himself via _base link)
        if ($this->_base) {
            if ($secOt == $this->ot_id) {
                $searchOtId = $this->_base->getID();
            } else {
                $searchOtId = $secOt;
            }
            $fr = $this->_base->getFamilyRelations($searchOtId, $secondTry);
        }

        // если и в ветке родителей не определен fr
        // - поиск по дереву искомого secОТ
        if (!$fr && !$secondTry) {
            $fr = $_sec->getFamilyRelations($this->ot_id, true);
            switch ($fr) {
                case 2:
                    $fr = 1;
                    break;
                case 1:
                    $fr = 2;
                    break;
                default:
                    $fr = false;
            }
        }

        return $fr;
    }

//  static public function getDirectionValue($direction){
//    if(is_string($direction) && array_key_exists($direction, self::$directionValues)){
//      return self::$directionValues[$direction];
//    }elseif(is_numeric($direction) && in_array($direction, self::$directionValues)){
//      return (int)$direction;
//    }
//  }

    function get_keys_assign_rules()
    {
        $result = array(
            0 => array(
                '0' => '0',
                'ot_id' => $this->ot_id,
                'rule' => 'base_object',
                'priority' => '100'
            )
        );

        $query = "SELECT `id`, `ot_id`, `rule`, `priority` FROM `" . SYS_DATABASE . "`.`_keys_assign_rules` WHERE `ot_id` = '" . $this->ot_id . "'";
        $oRes = $this->DB()->query($query);

        if ($oRes->getNumRows() > 0) {
            while ($row = $oRes->fetchRow()) {
                $result[$row['id']] = $row;
            }
        }

        return is_array($result) ? $result : false;
    }

    /**
     *
     * Создает связи между объектами
     *
     * @param mixed $prim_iid iid первичного
     * @param mixed $sec_array array(secOT => array(iid1, iid2 ...), secOT2=> ...) массив вторичных ОТ и их iid
     * @param mixed $ruleAlias false | string алиас правила связи если есть. (по какому правилу удалять если между объектами несколько возможных правил связи).
     * @param mixed $fr family Relation. Кто для кого кем является: 1 - primOt родитель, secOt - дети; 2,3 - secOt - родитель, primOt - дети;
     * @return array array(
     * '<affected>',
     * array(
     * 'p' => array(
     * <ot> => array(
     * <ruleAlias> => array(<iids>) // '' - for "no rule" case
     * ),...
     * ),
     * 'c' => same as p,
     * ),
     * )
     */
    function link($prim_iid, $sec_array, $ruleAlias = false, $fr = false, $extData = false)
    {
        return $this->make_remove_table_link('m', $prim_iid, $sec_array, $ruleAlias, $fr, $extData);
    }

    /**
     * Удаляет связи между объектами
     *
     * @param mixed $prim_iid iid первичного
     * @param mixed $sec_array array(secOT => array(iid1, iid2 ...), secOT2=> ...) массив вторичных ОТ и их iid
     * @param mixed $ruleAlias false | string алиас правила связи если есть. (по какому правилу удалять если между объектами несколько возможных правил связи).
     * @param mixed $fr family Relation. Кто для кого кем является: 1 - primOt родитель, secOt - дети; 2,3 - secOt - родитель, primOt - дети;
     * @param mixed $direction 1 - вниз, 2 - вверх, 3 - в обе стороны.
     * @return array
     * array(
     * '<affected>',
     * array(
     * 'p' => array(
     * <ot> => array(
     * <ruleAlias> => array(<iids>) // '' - for "no rule" case
     * ),...
     * ),
     * 'c' => same as p,
     * ),
     * );
     */
    function unlink($prim_iid, $sec_array, $ruleAlias = false, $fr = false)
    {// , $direction = 3
        return $this->make_remove_table_link('d', $prim_iid, $sec_array, $ruleAlias, $fr);
    }

    function updateLink($prim_iid, $sec_array, $ruleAlias = false, $fr = false, $extData = false)
    {
        return $this->make_remove_table_link('upd', $prim_iid, $sec_array, $ruleAlias, $fr, $extData);
    }

    /**
     * Создает либо удаляет связи между объектами
     *
     * @param mixed $action m - создание; d - удаление.
     * @param mixed $prim_iid iid первичного
     * @param mixed $sec_array array(secOT => array(iid1, iid2 ...), secOT2=> ...) массив вторичных ОТ и их iid
     * @param mixed $ruleAlias false | string алиас правила связи если есть. (по какому правилу удалять если между объектами несколько возможных правил связи).
     * @param mixed $fr family Relation. Кто для кого кем является: 1 - primOt родитель, secOt - дети; 2,3 - secOt - родитель, primOt - дети;
     * // * @param mixed $direction 1 - вниз, 2 - вверх, 3 - в обе стороны.
     * @return bool|array
     * array(
     * '<affected>',
     * array(
     * 'p' => array(
     * <ot> => array(
     * <ruleAlias> => array(<iids>) // '' - for "no rule" case
     * ),...
     * ),
     * 'c' => same as p,
     * ),
     * );
     */
    protected function make_remove_table_link($action = 'm', $prim_iid, $sec_array, $ruleAlias = false, $fr = false, $extData = false)
    { //$direction = 3,

        $r = array(
            0 => 0,
            array(
                'p' => array(),
                'c' => array(),
            )
        );

        if (!in_array($action, array('m', 'd', 'upd')) || !\Verba\convertToIdList($prim_iid)
            || ($action == 'upd' && empty($extData))) {
            return false;
        }

        reset($prim_iid);
        $c_p_iid = current($prim_iid);

        if (!is_array($sec_array) && is_numeric($sec_array) && $action == 'd') {
            $sec_array = array($sec_array => array());
        }

        if (!is_array($sec_array) || empty($sec_array)) {
            return $r;
        }

        $ruleAliasStr = is_string($ruleAlias) && !empty($ruleAlias) ? $ruleAlias : '';

        // only if its add new link into link_table-rule
        if (($action == 'm' || $action == 'upd') && is_array($extData) && !empty($extData)) {
            if ($action == 'm') {
                $extDataValues = '';
                $extDataFields = '';
                foreach ($extData as $fieldName => $fieldValue) {
                    $extDataFields .= ',`' . $fieldName . '`';
                    $extDataValues .= ",'" . $this->DB()->escape($extData[$fieldName]) . "'";
                }
            } else { // 'upd'
                $extDataValues = array();
                foreach ($extData as $fieldName => $fieldValue) {
                    $extDataValues[] = '`' . $fieldName . "` = '" . $this->DB()->escape($extData[$fieldName]) . "'";
                }
                $extDataValues = implode(', ', $extDataValues);
            }
        }

        foreach ($sec_array as $c_aot => $sec_iids) {
            \Verba\convertToIdList($sec_iids, true);

            $family_relations = $fr ? $fr : $this->getFamilyRelations($c_aot);

            $rule = $this->getRule($c_aot, $ruleAlias);

            $ruleAliasSql = $this->DB()->escape_string($rule['alias']);
            $rtype = $rule['rule'];
            $pix1 = '';
            $pix2 = '';

            switch ($family_relations) {
                case 2:
                case 3:
                    $pix1 = 'ch';
                    $pix2 = 'p';
                    $logKey = 'p';
                    if ($rtype == 'fid') {
                        if (!is_array($sec_iids) || empty($sec_iids)) {
                            continue 2;
                        }
                        reset($sec_iids);
                        $fid_new_value = current($sec_iids);
                        $fid_where = $this->DB()->makeWhereStatement($prim_iid, $this->getPAC(), 'lt');
                        $ot_field_value = $c_aot;
                    }
                    break;
                case 1:
                    $pix1 = 'p';
                    $pix2 = 'ch';
                    $logKey = 'c';
                    if ($rtype == 'fid') {
                        if (!is_array($sec_iids) || empty($sec_iids)) {
                            continue 2;
                        }
                        reset($prim_iid);
                        $fid_new_value = current($prim_iid);
                        $fid_where = $this->DB()->makeWhereStatement($sec_iids, $this->getPAC(), 'lt');
                        $ot_field_value = $this->getID();
                    }
                    break;
                default:
                    continue 2;
            }

            //Добавление
            if ($action == 'm' || $action == 'upd') {
                if (!isset($r[1][$logKey][$c_aot][$ruleAliasStr])) {
                    $r[1][$logKey][$c_aot][$ruleAliasStr] = $sec_iids;
                } else {
                    $r[1][$logKey][$c_aot][$ruleAliasStr] = array_merge($r[1][$logKey][$c_aot][$ruleAliasStr], $sec_iids);
                }


                switch ($rtype) {

                    case 'links_table':

                        $insert_values = '';
                        $where_pref = "'" . $ruleAliasSql . "','" . $this->getID() . "','" . $c_p_iid . "','" . $c_aot . "',";

                        foreach ($sec_iids as $c_sec_iid) {
                            $insert_values .= ",(" . $where_pref . "'" . $c_sec_iid . "'" . $extDataValues . ")";
                        }
                        if ($action == 'm') {
                            $querys2exec[] = "INSERT IGNORE INTO " . $rule['uri'] . " (`rule_alias`, `" . $pix1 . "_ot_id`, `" . $pix1 . "_iid`, `" . $pix2 . "_ot_id`, `" . $pix2 . "_iid`" . $extDataFields . ") VALUES " . substr($insert_values, 1);
                        } elseif ($action == 'upd') {

                            $querys2exec[] = "UPDATE IGNORE " . $rule['uri'] . " as lt
SET " . $extDataValues . "
WHERE `rule_alias` = '" . $ruleAliasSql . "'
&& `" . $pix1 . "_ot_id` = '" . $this->getID() . "'
&& `" . $pix1 . "_iid` = '" . $c_p_iid . "'
&& `" . $pix2 . "_ot_id` = '" . $c_aot . "'
&& `" . $pix2 . "_iid` IN (" . implode(', ', $sec_iids) . ")";

                        }
                        break;

                    case 'fid':
                        if ($rule['ot_field']) {
                            $ot_field_cond = ", `" . $rule['ot_field'] . "` = '" . $ot_field_value . "'";
                        } else {
                            $ot_field_cond = '';
                        }
                        $querys2exec[] = "UPDATE IGNORE " . $rule['uri'] . " as lt SET " . $rule['glue_field'] . " = '" . $fid_new_value . "'" . $ot_field_cond . " WHERE " . $fid_where;
                        break;
                }

                //Удаление
            } elseif ($action == 'd') {

                //$direction = ($direction = self::getDirectionValue($direction)) ? $direction : 3;

                if (!isset($r[1][$logKey][$c_aot][$ruleAliasStr])) {
                    $r[1][$logKey][$c_aot][$ruleAliasStr] = $sec_iids;
                } else {
                    $r[1][$logKey][$c_aot][$ruleAliasStr] = array_merge($r[1][$logKey][$c_aot][$ruleAliasStr], $sec_iids);
                }

                switch ($rtype) {
                    case 'links_table':
                        $i = array(array($pix1, $pix2));
//            if($direction == 3){
//              $i[] = array($pix2, $pix1);
//            }
                        foreach ($i as $idata) {
                            if (false !== ($where_statement = $this->DB()->makeWhereStatement($sec_iids, $idata[1] . '_iid'))) {
                                $where_statement = ' && (' . $where_statement . ')';
                            }

                            if (false !== ($where_prim_iid = $this->DB()->makeWhereStatement($prim_iid, $idata[0] . "_iid"))) {
                                $where_prim_iid = " && (" . $where_prim_iid . ")";
                            }

                            if (!empty($where_prim_iid)) {
                                $querys2exec[] = 'DELETE FROM ' . $rule['uri'] . ' WHERE `rule_alias` = \'' . $ruleAliasSql . '\' && `' . $idata[0] . '_ot_id` = ' . $this->getID() . ' ' . $where_prim_iid . ' && `' . $idata[1] . '_ot_id` = ' . $c_aot . $where_statement;
                            }
                        }
                        break;


                    case 'fid':
                        if ($rule['ot_field']) {
                            $ot_field_cond = ", `" . $rule['ot_field'] . "` = '0'";
                        } else {
                            $ot_field_cond = '';
                        }
                        $fid_new_value = '0';
                        $querys2exec[] = "UPDATE " . $rule['uri'] . " as lt SET " . $rule['glue_field'] . " = '" . $fid_new_value . "'" . $ot_field_cond . " WHERE " . $fid_where;
                        break;
                }
            }
        }

        if (is_array($querys2exec) && count($querys2exec) > 0) {
            foreach ($querys2exec as $query) {
                if ($oRes = $this->DB()->query($query, false)) {
                    $r[0] += $oRes->getAffectedRows();
                }
            }
        }

        return $r;
    }

    function generate_present_attribute_value($attr_code, &$row)
    {
        $A = $this->A($attr_code);
        $attr_code = $A->getCode();

        $aths = $A->getHandlers('present');
        $result = null;
        if (!is_array($aths) || empty($aths)) {
            //autohandler
            $handler = 'ph_' . $A->data_type . '_handler';
            if (method_exists($this, $handler)) {
                $aths = array(0 => array('_autohandler' => true, 'ah_name' => $A->data_type));
            } else {
                return $row[$attr_code];
            }
        }

        foreach ($aths as $set_id => $set_data) {
            $handler = 'ph_' . $set_data['ah_name'] . '_handler';
            $result = $this->$handler($A->getId(), $row, $set_id, $set_data, $result);
        }

        return $result;
    }

    /* *** Present handlers */
    function ph_foreign_id_handler($attr_id, $row)
    {
        $attr_code = $this->A($attr_id)->getCode();

        if (isset($row[$attr_code . '__value_' . SYS_LOCALE])) {
            return $row[$attr_code . '__value_' . SYS_LOCALE];
        } elseif (isset($row[$attr_code . '__value'])) {
            return $row[$attr_code . '__value'];
        }

        return isset($row[$attr_code]) || !empty($row[$attr_code])
            ? $row[$attr_code]
            : '';
    }

    function ph_long2ip_handler($attr_id, &$row)
    {
        return long2ip($row[$this->A($attr_id)->getCode()]);
    }

    function ph_logic_handler($attr_id, &$row)
    {
        $values = \Data\Boolean::getValues();
        $attr_code = $this->A($attr_id)->getCode();
        return array_key_exists($row[$attr_code], $values) ? $values[$row[$attr_code]] : $row[$attr_code];
    }

    function ph_exp_predefined_handler($attr_id, &$row)
    {
        $attr_code = $this->A($attr_id)->getCode();

        return is_array($row) && isset($row[$attr_code . '__value'])
            ? $row[$attr_code . '__value']
            : false;

    }

    function ph_make_strftime_handler($attr_id, &$row)
    {
        if (false === ($r = $row[$this->A($attr_id)->getCode()])) {
            return false;
        }
        $A = $this->A($attr_id);
        $timestamp = strtotime($r);
        $date = utf8fix(strtolower(strftime('%d %b %Y', $timestamp)));
        $label = '';
        if ($A->getType() == 'datetime') {
            $label = ' title="' . strftime('%H:%M', $timestamp) . '"';
        }
        $str = '<span class="litem-' . $A->getCode() . '-date"' . $label . '>' . $date . '</span>';

        return $str;
    }

    function ph_make_strftimetime_handler($attr_id, &$row)
    {
        return false != ($r = $row[$this->A($attr_id)->getCode()])
            ? utf8fix(strtolower(strftime('%d %b %Y %H:%M', strtotime($r))))
            : false;
    }

    function ph_multi_predefined_handler($attr_id, &$row)
    {
        $attr_code = $this->A($attr_id)->getCode();
        $attr_id = $this->A($attr_id)->getCode();

        if (!is_array($row) || !isset($row[$attr_code])) {
            return null;
        }

        $values = explode(',', $row[$attr_code]);
        $values_pdv = $this->A($attr_id)->getValues();

        return implode(', ', array_intersect_key($values_pdv, array_flip($values)));
    }

    function ph_unserialize_handler($attr_id, &$row)
    {
        $v = $row[$this->OT->id2code($attr_id)];
        return is_string($v)
            ? unserialize($v)
            : $row[$v];
    }

    function update($iid, $data)
    {
        $ae = $this->initAddEdit(array(
            'action' => 'edit',
            'iid' => $iid,
        ));
        $ae->setGettedData($data);
        $ae->addedit_object();
        return $ae;
    }

    function propCode2Id($code, $own = false)
    {
        if (array_key_exists($code, $this->OT->props_binds)) {
            return $this->OT->props_binds[$code];
        }
        if (!$own && $this->_base) {
            return $this->_base->propCode2Id($code, $own);
        }
        return false;
    }

    /**
     * @param $prop
     * @param bool $own
     * @return bool| \Verba\ObjectType\Property
     */
    function Prop($prop, $own = false)
    {

        if (is_object($prop) && $prop instanceof  \Verba\ObjectType\Property) {
            return $this->isProp($prop->getId(), $own) ? $prop : false;
        }
        if (!is_numeric($prop) && is_string($prop)) {
            $prop = $this->propCode2Id($prop);
        }
        if ($prop && array_key_exists($prop, $this->OT->props)) {
            return $this->OT->props[$prop];
        }
        if (!$own && $this->_base) {
            $prop = $this->_base->Prop($prop, $own);
            if (is_object($prop) && $prop->inheritable) {
                return $prop;
            } else {
                return false;
            }
        }
        return false;
    }

    function isProp($needle, $own = false)
    {
        if (is_string($needle) && array_key_exists($needle, $this->OT->props_binds)) {
            return true;
        }
        if (is_numeric($needle) && array_key_exists($needle, $this->OT->props)) {
            return true;
        }
        if (!$own && $this->_base) {
            $prop = $this->_base->Prop($needle, $own);
            if (is_object($prop) && $prop->inheritable) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * Возвращает значение свойства или null если свойства не существует
     * @param $prop
     * @return bool| \Verba\ObjectType\Property
     */
    function p($prop)
    {
        $prop = $this->Prop($prop);
        if (is_object($prop)) {
            return $prop->getValue();
        }
        return null;
    }

    /**
     * @param $data array|integer
     * @return \Verba\Model\Item|bool
     */
    function initItem($data, $cfg = array())
    {

        $OItemClass = $this->getOTItemClass();

        if (!is_array($data)) {
            if (is_numeric($data) || is_string($data)) {
                $data = array($data, $this->getID());
            }
        }

        try {
            $OItem = new $OItemClass($data, $cfg);
        } catch (\Exception $e) {
            return false;
        }

        return $OItem;
    }

    function getOTItemClass() {
        return is_string($this->OT->handler)
        && $this->OT->handler
            ? $this->OT->handler
            : '\Verba\Model\Item';
    }
}