<?php

namespace Verba\Html;

use \Verba\Act\Form\Element\Extender;

class Element extends \Verba\Configurable
{
    public $tag;
    public $name;
    public $id;
    public $value;
    public $E;

    public $disabled;
    public $readonly;

    public $classes = [];
    /**
     * HTML-element's DOM events
     * @var array
     */
    public $events = [];
    /**
     *  html-tag's attributes
     * @var array
     */
    public $attr = [];

    static public $validator_events_default = ['change', 'submit'];
    public $validators = [];
    public $_configApply = false;
    public $action;
    public $_clientCfg = false;
    /**
     * @var \Verba\FastTemplate Инициализируется
     */
    public $tpl;

    /**
     * Расширения элемента формы
     * @var array
     */
    protected $extensions = array();

    /**
     * @var \Verba\Act\Form\Element\Extender
     */
    public $AEFExtender;

    public $locale;

    // public $isLcd = false;

    /**
     * FormElement constructor.
     * @param bool $cfg
     * @param bool $extensions
     * @param bool $attr attribute sign - object|code|id
     * @param bool $ah
     */
    function __construct($cfg = false, $extensions = false, $attr = false, $ah = false)
    {
        $this->locale = SYS_LOCALE;

        if (is_object($ah)) {
            $this->AEFExtender = new Extender($this, $cfg, $attr, $ah);
        }

        if (is_array($cfg) && array_key_exists('extensions', $cfg)) {

            $extensions = is_array($extensions)
                ? array_replace_recursive($cfg['extensions'], $extensions)
                : $cfg['extensions'];

            unset($cfg['extensions']);
        }

        $this->applyConfigDirect($cfg);
        $this->fire('initBefore');
        $this->_init();
        $this->fire('initAfter');
        $this->setExtensions($extensions);
    }

    function __call($method, $args)
    {

        if (!is_array($realWorker = $this->_findSetter($method))) {

            if (method_exists($this, '_' . $method)) {
                $realWorker = [$this, '_' . $method];
            } else {
                throw new \Exception('Undefined class method called: ' . __CLASS__ . '::' . $method . '()');
            }
        }

        return call_user_func_array($realWorker, $args);
    }

    protected function _findSetter($k)
    {
        $callable = parent::_findSetter($k);

        if (is_array($callable)) {
            return $callable;
        }

        $method = $k;
        if (is_object($this->AEFExtender) && is_callable([$this->AEFExtender, $method])) {
            return array($this->AEFExtender, $method);
        }

        return false;
    }

    function __get($name)
    {
        if (property_exists($this, 'AEFExtender')
            && is_object($this->AEFExtender)
            && property_exists($this->AEFExtender, $name)) {
            return $this->AEFExtender->$name;
        }
        return null;
    }

    function _init()
    {
    }

    function setExtensions($extArray)
    {
        if (!is_array($extArray)
            || !($extArray = array_diff($extArray, array_keys($this->extensions)))
            || !count($extArray)) {
            return false;
        }

        foreach ($extArray as $ExtClassName => $extRawConf) {


            if (is_numeric($ExtClassName)) {
                if (array_key_exists('class', $extRawConf)) {
                    $ExtClassName = $extRawConf['class'];
                    unset($extRawConf['class']);
                } else {
                    $this->log()->error('Undefined Form Element extension class. cfg:' . var_export($extRawConf, true));
                    continue;
                }
            }

            // old scheme without namespace, deprecated
            if (strpos($ExtClassName, '\\') === false) {
                $ExtClassNameFull = '\Verba\Act\Form\Element\Extension\\' . ucfirst($ExtClassName);
            } else {
                $ExtClassNameFull = $ExtClassName;
            }
            if (!class_exists($ExtClassNameFull)) {
                $this->log()->error('Form Element extension class not exists. class: [' . var_export($ExtClassName, true) . '], cfg:' . var_export($extRawConf, true));
                continue;
            }

            if (array_key_exists('alias', $extRawConf)) {
                $ExtAlias = $extRawConf['alias'];
            } else {
                $ExtAlias = $ExtClassName;
            }

            $this->extensions[$ExtAlias] = new $ExtClassNameFull(
                $this,
                array_key_exists('cfg', $extRawConf) ? $extRawConf['cfg'] : $extRawConf
            );
        }
    }

    function clearExtensions()
    {
        $this->extensions = array();
    }

    function getExtensions()
    {
        return $this->extensions;
    }

    function getExtension($alias)
    {
        return array_key_exists($alias, $this->extensions)
            ? $this->extensions[$alias]
            : false;
    }

    function getTag()
    {
        return $this->tag;
    }

    function setTag()
    {
        return;
    }

    function _getValue()
    {
        return $this->value;
    }

    function _setValue($val)
    {
        $this->value = $val;
    }

    function attr()
    {
        $a = func_get_args();
        switch (count($a)) {
            case 0:
                return $this->attr;
            case 2:
                if (!is_string($a[0])) {
                    return false;
                }
                $this->attr[$a[0]] = (string)$a[1];
                return $this->attr[$a[0]];
            case 1:
                if (is_array($a[0])) {
                    foreach ($a[0] as $key => $val) {
                        $this->attr($key, $val);
                    }
                    return;
                }
                return is_string($a[0]) && array_key_exists($a[0], $this->attr) ? $this->attr[$a[0]] : null;

            default:
                return;
        }

    }

    function getAttr($key = null)
    {
        if (is_string($key)) {
            return array_key_exists($key, $this->attr)
                ? $this->attr[$key]
                : null;
        }

        return $this->attr;
    }

    function makeAttrs($prependWS = true)
    {
        if (empty($this->attr)) {
            return '';
        }
        $r = array();
        foreach ($this->attr as $k => $v) {
            $r[] = $k . '="' . htmlspecialchars($v) . '"';
        }
        return ($prependWS ? ' ' : '') . implode(' ', $r);
    }

    function makeAttr($name, $value)
    {
        return $value !== null
            ? $name . '="' . htmlspecialchars($value) . '"'
            : '';
    }

    function removeAttr($attr)
    {
        if (array_key_exists($attr, $this->attr)) {
            unset($this->attr[$attr]);
            return true;
        }
        return false;
    }

    function setName($var)
    {
        if (is_string($var)) {
            $this->name = $var;
        }
    }

    function getName()
    {
        return $this->name;
    }

    function makeNameTagAttr()
    {
        return is_string($this->name) ? 'name="' . $this->name . '"' : '';
    }

    function setId($var)
    {
        if (is_string($var) && !empty($var)) {
            $this->id = $var;
        }
    }

    function getId()
    {
        if ($this->id === null) {
            if (is_callable(array($this, 'makeId'), true)) {
                $this->id = $this->makeId();
            }
        }
        return $this->id;
    }

    function makeIdTagAttr()
    {
        return is_string($this->id) ? 'id="' . $this->id . '"' : '';
    }

    function setDisabled($val)
    {
        $this->disabled = (bool)$val;
    }

    function getDisabled()
    {
        return $this->disabled;
    }

    function makeDisabled()
    {
        return $this->disabled ? 'disabled' : '';
    }

    function setReadonly($val)
    {
        $this->readonly = (bool)$val;
    }

    function getReadonly()
    {
        return $this->readonly;
    }

    function makeReadonly()
    {
        return $this->readonly ? 'readonly = "readonly"' : '';
    }

    function setClasses($classes)
    {
        if (!\Verba\reductionToArray($classes, ' ')) return false;
        $this->classes = $classes;
    }

    function addClasses($classes)
    {
        if (!\Verba\reductionToArray($classes, ' ')) return false;
        $this->classes = array_unique(array_merge($this->classes, $classes));
    }

    function removeClasses($classes)
    {
        if (!\Verba\reductionToArray($classes, ' ')) return false;
        $this->classes = array_diff($this->classes, $classes);
    }

    function getClasses()
    {
        return $this->classes;
    }

    function haveClass($class)
    {
        return is_array($this->classes) && in_array($class, $this->classes);
    }

    function makeClassesTagAttr()
    {
        return count($this->classes) > 0
            ? 'class="' . implode(' ', $this->classes) . '"'
            : '';
    }

    function setEvents($events, $codeStr = false)
    {
        if (is_string($events) && !empty($events) && is_string($codeStr)) {
            $events = array($events => array($codeStr));
        }
        if (!is_array($events)) return false;
        foreach ($events as $eventName => $c_codes) {
            $eventName = strtolower($eventName);
            if (!\Verba\reductionToArray($c_codes)) continue;

            if (!array_key_exists($eventName, $this->events))
                $this->events[$eventName] = array();

            $this->events[$eventName] = array_merge($this->events[$eventName], $c_codes);
        }
    }

    function getEvents()
    {
        return $this->events;
    }

    function makeEventsTagAttr()
    {
        if (count($this->events) < 1) return '';
        $evs = array();
        foreach ($this->events as $evName => $evCodes) {
            $evs[] = "on$evName=\"" . implode(';', $evCodes) . ";\"";
        }
        return implode(' ', $evs);
    }

    static function getValidatorEventsDefault()
    {
        return self::$validator_events_default;
    }

    /**
     * array(url => array('a' => $args, 'e' => array('change', 'submit')), 'required' => array(e => array('change', 'submit')));
     */

    function setValidators($validators, $events = false, $cfg = false)
    {
        if (!$events) $events = self::$validator_events_default;
        if (is_string($validators)) {
            $validators = array($validators => array('e' => $events, 'cfg' => $cfg));
        }
        if (!is_array($validators)) return false;
        foreach ($validators as $vName => $vData) {
            if (!is_array($vData)) {
                $vName = $vData;
                $vData = false;
            }

            $c_events = is_array($vData) && array_key_exists('e', $vData) ? $vData['e'] : $events;
            $c_cfg = is_array($vData) && array_key_exists('cfg', $vData)
                ? $vData['cfg']
                : (is_array($cfg)
                    ? $cfg
                    : false);

            $V = \Data::getValidator($vName, $c_cfg);
            if (!$V) {
                continue;
            }

            $this->validators[$vName] = array('e' => $c_events, 'v' => $V);
        }
    }

    function getValidators()
    {
        return $this->validators;
    }

    function setE($str)
    {
        if (is_string($str) && !empty($str))
            $this->E = $str;
    }

    function getE()
    {
        return $this->E;
    }

    function _build()
    {
        $this->makeE();
        return $this->getE();
    }

    function parse()
    {
        return $this->build();
    }

    function asCfg()
    {
        return $this->exportAsCfg();
    }

    function exportAsCfg()
    {
        $r = array();

        $r['name'] = $this->getName();
        $r['id'] = $this->getId();
        if ($this->getTag() !== null) $r['tag'] = $this->getTag();
        if ($this->getValue() !== null) $r['value'] = $this->getValue();
        if ($this->getDisabled() !== null) $r['disabled'] = $this->getDisabled();
        if (count($this->getClasses()) > 0) $r['classes'] = $this->getClasses();
        if (count($this->getEvents()) > 0) $r['events'] = $this->getEvents();
        if (is_array($this->attr) && !empty($this->attr)) {
            $r['attr'] = $this->attr;
        }
        return $r;
    }

    static function implodeTagAttrs($attrs)
    {
        $r = '';
        if (!is_array($attrs) || !count($attrs)) {
            return $r;
        }
        foreach ($attrs as $v) {
            if (!settype($v, 'string') || !strlen($v)) {
                continue;
            }
            $r .= ' ' . $v;
        }
        return $r;
    }

    function prepareEAttrs()
    {

        $ia = array();
        $ia['attrs'] = $this->makeAttrs(false);
        $ia['name'] = $this->makeNameTagAttr();
        $ia['id'] = $this->makeIdTagAttr();
        $ia['disabled'] = $this->makeDisabled();
        $this->fire('addClasses');
        $this->fire('addEvents');
        $ia['classes'] = $this->makeClassesTagAttr();
        $ia['events'] = $this->makeEventsTagAttr();
        $ia['readonly'] = $this->makeReadonly();

        return $ia;
    }

    function prepareEAttrsImploded()
    {
        $ia = $this->prepareEAttrs();
        if (is_array($ia) && count($ia)) {
            return self::implodeTagAttrs($ia);
        }
        return '';
    }

    function packToClient()
    {

        $r = array();
        if (is_array($this->_clientCfg) && count($this->_clientCfg)) {
            $r = $this->_clientCfg;
        }
        if (!array_key_exists('data', $r)) {
            $r['data'] = array();
        }

        $r['id'] = $this->getAcode();
        $r['eid'] = $this->getId();
        $r['acode'] = $this->acode;
        $r['lcd'] = $this->isLcd;

        return $r;
    }
}
