<?php
namespace Verba;

class Configurable extends Eventer
{
    protected $_confPrefix;
    protected $_confPath;
    protected $_confPropName;
    protected $_confCreatePropOnFly = false;
    protected $_confOnlyPubPropDirectSetAllowed = true;
    protected $_confApplied = array();
    protected $_confAppliedNames = array();
    protected $_confPropsMeta = array();
    protected $_c;

    public static $_config_default = array();

    protected function _findSetter($key)
    {
        $mtd = 'set' . ucfirst($key);

        if (method_exists($this, $mtd)) {
            return [$this, $mtd];
        }

        if (false !== strpos($key, '_')) {
            $frgs = explode('_', $key);
            $mtd = 'set';
            foreach ($frgs as $f) {
                if ($f === '') {
                    continue;
                }
                $mtd .= ucfirst($f);
            }
            if (method_exists($this, $mtd)) {
                return [$this, $mtd];
            }
        }
        return false;
    }

    protected function initConfigurator($path = null, $prefix = null, $confPropName = null)
    {
        if ($path !== null)
            $this->setConfPath($path);
        if ($prefix !== null)
            $this->setConfPrefix($prefix);
        if ($confPropName !== null)
            $this->setConfPropName($confPropName);

        if (is_string($this->_confPropName)
            && strlen($this->_confPropName)) {
            if (!property_exists($this, $this->_confPropName) || !is_array($this->{$this->_confPropName})) {
                $this->{$this->_confPropName} = array();
            }
            $this->_c = &$this->{$this->_confPropName};
        }

    }

    function __getDefaultConf()
    {
        return self::$_config_default;
    }

    function setConfPath($val)
    {
        $this->_confPath = (string)$val;
    }

    function getConfPath()
    {
        return $this->_confPath;
    }

    function setConfPrefix($val)
    {
        $this->_confPrefix = (string)$val;
    }

    function getConfPrefix()
    {
        return $this->_confPrefix;
    }

    function setConfPropName($val)
    {
        $this->_confPropName = (string)$val;
    }

    function getConfPropName()
    {
        return $this->_confPropName;
    }

    function setConfMode($val)
    {
        $this->_confMode = (int)$val;
    }

    function getConfMode()
    {
        return $this->_confMode;
    }

    function getConfPathByName($name)
    {
        $dir = $this->_confPath;
        if (strpos($name, '/') !== false) {
            $name_frags = explode('/', $name);
            $name = array_pop($name_frags);

            foreach ($name_frags as $cdir) {
                if (empty($cdir)) {
                    continue;
                }
                $dir .= '/' . $cdir;
            }
        }

        $prefix = !empty($this->_confPrefix)
            ? $this->_confPrefix . '.'
            : '';

        return $dir . '/' . $prefix . $name . '.php';
    }

    /**
     * Apply config props to object
     *
     * @param array $cfg
     * @param array $params [name, path]
     * @param string|array $subcases Syntax for config into config like ':' => <subcase> => array( ... subcase config values ... )
     */

    public function _applyConfig($cfg, $params = array())
    {

        $cfg = (array)$cfg;

        if (empty($cfg)) {
            return;
        }

        $params = (array)$params;


        $this->_confApplied[] = isset($params['path']) && is_string($params['path'])
            ? $params['path']
            : 'unknown';

        $this->_confAppliedNames[] = isset($params['name']) && is_string($params['name'])
            ? $params['name']
            : 'unknown';

        $configs = array(
            array(),
            $cfg
        );

        if (isset($cfg[':'])) {
            $subcases = isset($params['subcases'])
                ? (array)$params['subcases']
                : false;

            if ($subcases) {
                foreach ($subcases as $scase) {
                    if (isset($cfg[':'][$scase]) && is_array($cfg[':'][$scase])) {
                        $this->_confApplied[] = ':' . $scase;
                        $this->_confAppliedNames[] = ':' . $scase;
                        $configs[] = $cfg[':'][$scase];
                    }
                }
            }
            unset($cfg[':']);
        }

        $combined_config = call_user_func_array('array_replace_recursive', $configs);
        if (!is_array($combined_config) || !count($combined_config)) {
            return;
        }

        $this->applyChangeToProps($combined_config);
        return;
    }

    function applyConfig($cfgNamesArr, $params = array())
    {

        if (!\Verba\reductionToArray($cfgNamesArr, ' ')) {
            return false;
        }
        foreach ($cfgNamesArr as $cconfName) {

            if (!$cconfName
                || !is_string($cconfigFilePath = $this->getConfPathByName($cconfName))
                || !file_exists($cconfigFilePath)
                || !is_array($cfg = @include($cconfigFilePath))) {
                continue;
            }

            if (!is_array($params)) {
                $params = array();
            }

            $params['name'] = $cconfName;
            $params['path'] = \Verba\Hive::getRealIncludeLocation($cconfigFilePath);

            $this->_applyConfig($cfg, $params);
        }
        return true;
    }

    function applyConfigDirect($cfg, $params = array())
    {
        if (!is_array($cfg) || !count($cfg)) {
            return false;
        }
        if (!is_array($params)) {
            $params = (array)$params;
        }

        if (!isset($params['name']) || !is_string($params['name'])) {
            $params['name'] = 'direct';
        }
        $params['path'] = 'direct';

        return $this->_applyConfig($cfg, $params);
    }

    public function getPublicVars()
    {
        return \Verba\get_object_vars_public($this);
    }

    private function applyChangeToProps($cfg)
    {

        $pubvars = $this->getPublicVars();

        $confPropName = $this->getConfPropName();
        $i = 0;
        $haveMetaProps = (bool)count($this->_confPropsMeta);
        foreach ($cfg as $k => $v) {

            if (is_array($setter = $this->_findSetter($k))) {

                $setter[0]->{$setter[1]}($v);

            } else {


                if (!is_string($confPropName) || !strlen($confPropName)) {
                    // если прямая запись не-паблик свойства запрещена
                    if ($this->_confOnlyPubPropDirectSetAllowed
                        && !array_key_exists($k, $pubvars)) {
                        continue;
                    }

                    if (!property_exists($this, $k)) {
                        if (!$this->_confCreatePropOnFly) {
                            continue;
                        } else {
                            $this->{$k} = null;
                        }
                    }

                    $p = &$this->{$k};
                } else {
                    if (!array_key_exists($k, $this->$confPropName)) {
                        $this->{$confPropName}[$k] = null;
                    }

                    $p = &$this->{$confPropName}[$k];
                }
                // Если значение объект \Verba\Configurable\Prop\Restore
                // восстановление знеачения из конфига по умолчанию
                // TO DO восстановление на данный момент только ключа первого уровня
                // Для реализации этой функции для ключа любой глубины доработать функцию
                // self::array_replace_keys_recursive() и пользовать ее
                // в этом случае, первый if не понадобится
                if (is_object($v) && $v instanceof \Verba\Configurable\Prop\Restore) {
                    $p = $v->run($this, $k);
                } elseif (is_array($p) && !empty($p) && is_array($v)) {
                    $p = array_replace_recursive($p, $v);
                } else {
                    if ($haveMetaProps
                        && array_key_exists($k, $this->_confPropsMeta)
                        && !empty($this->_confPropsMeta[$k])) {

                        $propMeta = $this->_confPropsMeta[$k];
                        if (isset($propMeta['dataType'])) {
                            settype($v, $propMeta['dataType']);
                        }
                    }
                    $p = $v;
                }
            }
            $i++;
        }
        return (bool)$i;
    }

    function sC($value)
    {
        $args = func_get_args();
        $mtd = !$this->_confPropName ? 'sCObject' : 'sCArray';

        $r = call_user_func_array(array($this, $mtd), $args);
        return $r;
    }

    function sCArray($value)
    {
        $args = func_get_args();
        array_shift($args);

        if (!count($args)) {
            return false;
        }

        if (count($args) == 1 && strpos($args[0], ' ') !== false) {
            $args = preg_split("/\s+/", $args[0]);
        }
        $num = count($args);
        $v = &$this->{$this->_confPropName};
        for ($i = 0; $i <= $num; $i++) {
            if ($i < $num) { // not current
                $key = $args[$i];
                if (is_array($v)) {
                    if (!array_key_exists($key, $v)) {
                        $v[$key] = null;
                    }
                } else {
                    $v = array($key => null);
                }
                $v = &$v[$key];
                continue;
            }
            $v = $value;
        }
        return true;
    }

    function sCObject($value)
    {
        $args = func_get_args();
        array_shift($args);

        if (!count($args)) {
            return false;
        }

        if (count($args) == 1 && strpos($args[0], ' ') !== false) {
            $args = preg_split("/\s+/", $args[0]);
        }

        $num = count($args);
        $v = &$this;
        for ($i = 0; $i <= $num; $i++) {
            $key = $args[$i];
            if ($i < $num) { // not target
                if (is_object($v)) {
                    if (!property_exists($v, $key)) {
                        $v->$key = null;
                    }
                } else {
                    $v = new stdClass();
                    $v->$key = null;
                }
                $v = &$v->$key;
            } else {
                $v = $value;
            }
        }
        return true;
    }

    function gC()
    {
        $args = func_get_args();
        $mtd = !$this->_confPropName ? 'gCObject' : 'gCArray';

        $r = call_user_func_array(array($this, $mtd), $args);
        return $r;
    }

    function gCArray()
    {
        $args = func_get_args();
        if (!count($args)) {
            return $this->{$this->_confPropName};
        }
        if (count($args) == 1 && strpos($args[0], ' ') !== false) {
            $args = preg_split("/\s+/", $args[0]);
        }

        $v = &$this->{$this->_confPropName};
        foreach ($args as $c_node) {
            if (!is_array($v) || !array_key_exists($c_node, $v)) {
                return null;
            }
            $v = &$v[$c_node];
        }
        return $v;
    }

    function gCObject()
    {
        $args = func_get_args();
        if (!count($args)) {
            return $this;
        }
        if (count($args) == 1 && strpos($args[0], ' ') !== false) {
            $args = preg_split("/\s+/", $args[0]);
        }

        $v = &$this;
        foreach ($args as $c_node) {
            if (!property_exists($v, $c_node)) {
                return null;
            }
            $v = &$v->$c_node;
        }
        return $v;
    }

    static function substNumIdxAsStringValues($arr, $defaultValue = array())
    {
        if (!is_array($arr) || !count($arr)) {
            return $arr;
        }
        $r = array();
        foreach ($arr as $k => $v) {
            if (!is_numeric($k)) {
                $r[$k] = $v;
                continue;
            }
            $r[$v] = $defaultValue;
        }
        return $r;
    }

}
