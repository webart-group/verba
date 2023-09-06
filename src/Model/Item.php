<?php
namespace Verba\Model;

class Item extends \Verba\Configurable
{

    protected $otype;
    protected $_confPropName = 'data';
    protected $data = array();
    protected $_prepared = array();
    protected $_requiredData = true;
    protected $allLangMode = false;
    protected $internalLang = true;

    /**
     * @var \Verba\Model
     */

    protected $oh;

    private $_prep_alm;
    private $_prep_lc;
    /**
     * @var bool|\Verba\ObjectType\Attribute
     */
    private $_prep_A;

    /**
     * @param $data
     * @param array $cfg
     * @throws \Exception
     */
    function __construct($data, $cfg = array())
    {

        if ($this->otype) {
            $this->oh = \Verba\_oh($this->otype);
        }

        if (is_array($cfg) && count($cfg)) {
            $this->applyConfigDirect($cfg);
        }

        if (!is_array($data) && (is_string($data) || is_numeric($data))) {
            if (!$this->oh) {
                throw new \Exception('Unknown OT to load data');
            }
            $data = $this->loadData($data);

        } elseif (is_array($data)) {

            if (count($data) == 2 && array_key_exists(0, $data) && array_key_exists(1, $data)) {

                if (!$this->oh) {
                    $this->oh = \Verba\_oh($data[1]);
                }

                $data = $this->loadData($data[0]);

            }
            if (!$this->oh && array_key_exists('ot_id', $data)) {
                $this->oh = \Verba\_oh($data['ot_id']);
            }
        }

        if (!$this->oh || ($this->_requiredData && !is_array($data))) {
            throw new \Exception('Bad incoming data');
        }

        if (!$this->otype) {
            $this->otype = $this->oh->getCode();
        }

        $this->applyConfigDirect($data);


        $this->init();
    }

    function __call($mth, $args)
    {
        $action = strtolower(substr($mth, 0, 3));
        $propertie = lcfirst(substr($mth, 3));

        if ($action == 'get'
            && is_string($propertie)
            && array_key_exists($propertie, $this->{$this->_confPropName})) {
            return $this->p($propertie);
        }

        if ($action == 'set') {
            return $this->__set($propertie, $args[0]);
        }

        throw new \Exception('Call undefined method - ' . __CLASS__ . '::' . $mth . '()');
    }

    function __get($propName)
    {
        $mtd = 'get' . ucfirst($propName);
        if (method_exists($this, $mtd)) {
            return $this->$mtd();
        }
        return $this->getNatural($propName);
    }

    function __set($propName, $val)
    {
        $mtd = 'set' . ucfirst($propName);
        if (method_exists($this, $mtd)) {
            return $this->$mtd($val);
        }

        if(is_object($A = $this->oh->A($propName))) {
            $datatypeMthd = '__set_datatype_'.$A->getDataType();
            if(method_exists($this, $datatypeMthd)){
                return $this->$datatypeMthd($val);
            } else {
                $this->data[$propName] = $val;
                return $this;
            }
        }

        /*else(array_key_exists($propName,$this->data)){
          $this->data[$propName] = $val;
          return $this->data[$propName];
        }*/
        return null;
    }

    function __isset($prop)
    {
        return array_key_exists($prop, $this->data);
    }

    function __wakeup()
    {
        $this->oh = \Verba\_oh($this->otype);
    }

    function __sleep()
    {
        $vars = get_object_vars($this);
        unset($vars['DB'],
            $vars['log'],
            $vars['oh'],
            $vars['_prep_alm'],
            $vars['_prep_lc'],
            $vars['_prep_A']
        );
        return array_keys($vars);
    }

    function init()
    {

    }

    protected function loadData($iid)
    {
        return $this->oh->getData($iid, 1, true, false, $this->allLangMode, true, ($this->allLangMode ? true : false));
    }

    function getOtype()
    {
        return $this->otype;
    }

    function setOtype($val)
    {
        // не перезаписывается
        return $this->otype;
    }

    function getOtId()
    {
        return $this->oh->getID();
    }

    function oh()
    {
        return $this->getOh();
    }

    function getOh()
    {
        return $this->oh;
    }

    function getId()
    {
        return $this->data[$this->oh->getPAC()];
    }

    function setId($val)
    {
        $this->data[$this->oh->getPAC()] = (int)$val;
    }

    function getIid()
    {
        return $this->getId();
    }

    function toArray()
    {
        return $this->data;
    }

    function p($propName, $val = '~|!$#~')
    {

        return $val === '~|!$#~'

            ?
            $this->getValue($propName)

            :
            $this->applyConfigDirect(array(
                $propName => $val
            ));

    }

    function getValue($propName)
    {

        return $this->getPrepared($propName);

    }

    function isProp($propName)
    {
        return is_string($propName) && array_key_exists($propName, $this->{$this->_confPropName});
    }

    function getRawValue($propName)
    {
        return array_key_exists($propName, $this->data)
            ? $this->data[$propName]
            : null;
    }

    function getNatural($propName, $lang = false)
    {
        if (!array_key_exists($propName, $this->data)) {
            return null;
        }

        if ($this->allLangMode
            && $this->oh->isA($propName)
            && $this->oh->A($propName)->isLcd()
        ) {
            if (!is_array($this->data[$propName])) {
                $this->allLangMode = false;
                goto RETURN_AS_NORMAL;
            }

            $lang = !$lang || !\Verba\Lang::isLCValid($lang) ? $this->internalLang : $lang;
            return array_key_exists($lang, $this->data[$propName])
                ? $this->data[$propName][$lang]
                : null;
        }

        RETURN_AS_NORMAL:
        return $this->data[$propName];
    }

    function getPrepared($propName)
    {

        if (!array_key_exists($propName, $this->data)) {
            return null;
        }

        $this->_prep_alm = false;
        $this->_prep_lc = null;

        $this->_prep_A = $this->oh->A($propName);
        if ($this->allLangMode && $this->_prep_A && (
                $this->_prep_A->isLcd()
                || $this->_prep_A->isPredefined()
                || $this->_prep_A->isForeignId()
            )) {
            $this->_prep_alm = true;
            $this->_prep_lc = $this->internalLang;
        }

        if (!array_key_exists($propName, $this->_prepared)) {

            $this->_prepared[$propName] = null;

            if ($this->_prep_alm) {
                $this->_prepared[$propName] = array_fill_keys(\Verba\Lang::getUsedLC(), '');
                foreach (\Verba\Lang::getUsedLC() as $clc) {
                    $this->_prep_lc = $clc;
                    $this->_prepared[$propName][$clc] = $this->prepareValue($propName);
                }

            } else {
                $this->_prepared[$propName] = $this->prepareValue($propName);
            }
        }

        $r = $this->_prep_alm ? $this->_prepared[$propName][$this->internalLang] : $this->_prepared[$propName];

        $this->_prep_alm = false;
        $this->_prep_lc = null;

        return $r;
    }

    function prepareValue($propName)
    {

        $method = 'prepareValue_' . $propName;
        $this->_prep_A = $this->oh->A($propName);
        if (!method_exists($this, $method)
            && $this->_prep_A
        ) {
            $method = 'prepareValueAttr';
        } else {
            $method = 'prepareValueDefault';
        }

        return $this->$method($propName);

    }

    protected function prepareValueDefault($propName)
    {

        if (!array_key_exists($propName, $this->data)) {
            return null;
        }

        if ($this->_prep_A
            && $this->_prep_A->isLcd()
            && is_array($this->data[$propName])
            && $this->_prep_lc
            && array_key_exists($this->_prep_lc, $this->data[$propName])
        ) {
            return $this->data[$propName][$this->_prep_lc];
        }

        return $this->data[$propName];
    }

    protected function prepareValueAttr($propName)
    {
        if ($this->_prep_A->isPredefined()) {
            $method = 'prepareValuePredefined';
        }

        if ($this->_prep_A->isForeignId()) {
            $method = 'prepareValueForeignId';
        }

        if (isset($method)) {
            return $this->$method($propName);
        }

        $aths = $this->_prep_A->getHandlers('present');
        if (is_array($aths) && count($aths)) {

            foreach ($aths as $set_id => $set_data) {

                list($handlerClass, $handlerCfg) = \Verba\Hive::stringToHandlerParts($set_data['ah_name']);

                if (!class_exists($handlerClass)) {
                    $handler = 'ph_' . $set_data['ah_name'] . '_handler';
                    if (!method_exists($this->oh(), $handler)) {
                        $this->log()->error('Unknown present ah for attribute [' . $this->_prep_A->getCode() . ', oh: ' . $this->oh()->getCode() . '], classname: [' . var_export($handlerClass, true) . ']');
                    } else {
                        $r = $this->oh()->$handler($this->_prep_A->getId(), $this->data, $set_id, $set_data, $this->data[$propName]);
                    }

                } else {
                    /**
                     * @var $handler \Verba\ObjectType\Attribute\Handler
                     */
                    $handler = new $handlerClass($this->oh, $this->_prep_A, $handlerCfg);
                    $handler->setValue($this->data[$propName]);
                    $r = $handler->run();
                }
            }
        }

        if (isset($r)) {
            return $r;
        }

        $method = 'prepareValue' . ucfirst($this->_prep_A->getDataType());
        if (!method_exists($this, $method)) {
            $method = 'prepareValueDefault';
        }
        return $this->$method($propName);
    }

    function getPreparedIds($propName)
    {
        if ($this->getPrepared($propName) === null) {
            return null;
        }
        $k = '__' . $propName . '_values';
        if (!array_key_exists($k, $this->_prepared)) {
            return null;
        }

        return $this->internalLang
        && is_array($this->internalLang)
        && array_key_exists($this->internalLang, $this->_prepared[$k])
            ? $this->_prepared[$k][$this->internalLang]
            : $this->_prepared[$k];
    }

    function prepareValueMultiple($propName)
    {

        $preparedValuesKey = '__' . $propName . '_values';
        $dataKey = $propName . '__value';

        if ($this->_prep_alm) {
            $clc = $this->internalLang;
            if (!isset($this->_prepared[$preparedValuesKey][$clc])) {
                $this->_prepared[$preparedValuesKey][$clc] = array();
            }
            $pvalues = &$this->_prepared[$preparedValuesKey][$clc];
            $dataKey = !isset($this->data[$dataKey . '_' . $clc])
                ? (isset($this->data[$dataKey]) ? $dataKey : false)
                : $dataKey . '_' . $clc;
        } else {

            $pvalues = &$this->_prepared[$preparedValuesKey];

        }

        if (!$dataKey || empty($this->data[$dataKey])) {
            return null;
        }

        $values = explode('#', $this->data[$dataKey]);
        // Attr as Filter
        foreach ($values as $cvalue) {
            $vd = explode(':', $cvalue);
            $pvalues[$vd[0]] = $vd[1];
        }

        return count($pvalues) ? implode(', ', $pvalues) : '';
    }

    function prepareValuePredefined($propName)
    {
        if ($this->_prep_A && $this->_prep_A->getDataType() == 'multiple') {
            return $this->prepareValueMultiple($propName);
        }

        $valueDataKey = $propName . '__value' . ($this->_prep_lc ? '_' . $this->_prep_lc : '');

        $r = isset($this->data[$valueDataKey]) && !empty($this->data[$valueDataKey])
            ? $this->data[$valueDataKey]
            : (isset($this->data[$propName . '__value'])
                ? $this->data[$propName . '__value']
                : $this->data[$propName]);

        return $r;
    }

    function prepareValueForeignId($propName)
    {

        $valueDataKey = $propName . '__value' . (
            $this->_prep_lc && isset($this->data[$propName . '__value_' . $this->_prep_lc])
                ? '_' . $this->_prep_lc
                : '');

        $r = isset($this->data[$valueDataKey])
        && !empty($this->data[$valueDataKey])
            ? $this->data[$valueDataKey]
            : $this->data[$propName];

        return $r;
    }

    function prepareValueLogic($propName)
    {
        $v = (int)!!($this->data[$propName]);
        $values = \Verba\Data\Boolean::getValues();

        return $values[$v];
    }

    function prepareValueFloat($propName)
    {
        $r = null;
        if (array_key_exists($propName, $this->data)) {
            if (is_float($this->data[$propName])) {
                $r = &$this->data[$propName];
            } else {
                $r = (float)$this->data[$propName];
            }
        }

        return $r;
    }

    function prepareValueMoney($propName)
    {
        $r = false;
        if (array_key_exists($propName, $this->data)) {
            if (is_double($this->data[$propName])) {
                $r = &$this->data[$propName];
            } else {
                $r = \Verba\reductionToCurrency($this->data[$propName]);
            }
        }

        return $r;
    }

    function prepareValueSerialized($propName)
    {

        if (array_key_exists($propName, $this->data)
            && is_string($this->data[$propName])
            && !empty($this->data[$propName])) {
            return unserialize($this->data[$propName]);
        }

        return null;
    }

    function getPropsNatural($props)
    {
        if (!is_array($props) || !count($props)) {
            return false;
        }
        $r = array();
        foreach ($props as $prop) {
            $r[$prop] = $this->getNatural($prop);
        }
        return $r;
    }

    function exportAsValues($keys = null)
    {
        $r = array();

        if (is_string($keys)) {
            $keys = (array)$keys;
        }

        if (is_null($keys)) {
            $keys = $this->oh()->getAttrs(true);
        }

        foreach ($keys as $key) {
            $natVal = $this->getNatural($key);
            $val = $this->getValue($key);
            if ($natVal !== null) {
                $r[$key] = $natVal;
            }

            if ($natVal !== $val) {
                $r[$key . '__value'] = $val;
            }
        }

        return $r;
    }

    function update($data)
    {
        $ae = $this->oh->initAddEdit(array(
            'action' => 'edit',
            'iid' => $this->getId(),
        ));
        $ae->setGettedData($data);
        $ae->addedit_object();
        return $ae;
    }

    function getImageAttrUrl($copy_alias = 'primary', $pic_attr_code = 'picture')
    {

        if (!$copy_alias) {
            $copy_alias = 'primary';
        }
        $alias = '__' . $pic_attr_code . '_urls';

        if (!array_key_exists($alias, $this->_prepared)) {
            $this->_prepared[$alias] = array();
        }

        if (array_key_exists($copy_alias, $this->_prepared[$alias]) && $this->_prepared[$alias][$copy_alias] !== null) {
            return $this->_prepared[$alias][$copy_alias];
        }

        $this->_prepared[$alias][$copy_alias] = $this->makeImageAttrUrl($copy_alias, $pic_attr_code);

        return $this->_prepared[$alias][$copy_alias];
    }

    function makeImageAttrUrl($copy_alias, $pic_attr_code)
    {
        $picvalue = $this->getNatural($pic_attr_code);
        $piccfgvalue = $this->oh()->p($pic_attr_code . '_config');
        if (empty($picvalue) || empty($piccfgvalue)) {
            return '';
        }
        /**
         * @var $mImage Image
         */
        $mImage = \Verba\_mod('image');

        $iCfg = $mImage->getImageConfig($piccfgvalue);
        if (!$iCfg) {
            return '';
        }
        return $iCfg->getFullUrl(basename($picvalue), $copy_alias);
    }

    function setAllLangMode($val)
    {
        $this->allLangMode = (bool)$val;
        if ($this->allLangMode) {
            if (!is_string($this->internalLang)) {
                $this->internalLang = SYS_LOCALE;
            }
        }
        return $this->allLangMode;
    }

    function internalLang($val = null)
    {
        return ($val === null) ? $this->internalLang : $this->setInternalLang($val);
    }

    function setInternalLang($val)
    {
        $this->internalLang = \Verba\Lang::isLCValid($val) ? $val : (is_bool($val) ? $val : $this->internalLang);
    }

}
