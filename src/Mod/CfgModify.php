<?php

namespace Verba\Mod;

class CfgModify extends \Verba\Mod
{

    public $targetMod;
    use \Verba\ModInstance;
    function getCustomizableConfigKeys($mod = false)
    {

        $mod = is_object($mod)
            ? $mod
            : (is_object($this->targetMod) ? $this->targetMod : false);

        if (!$mod) {
            throw new \Exception('Bad $mod.');
        }

        $keys = $mod->gC('_customizable keys');
        return !$keys || !count($keys) ? array() : $keys;
    }

    function customizeCfgNow($mod = false)
    {
        $mod = is_object($mod)
            ? $mod
            : (is_object($this->targetMod) ? $this->targetMod : false);
        if (!$mod) {
            throw new \Exception('Bad $mod.');
        }

        $keys = $this->getCustomizableConfigKeys($mod);
        if (!count($keys)) {
            return false;
        }

        $cstm = $mod->gC('_customizable');
        $i = 0;
        foreach ($keys as $key => $keyData) {
            $ename = str_replace(' ', '-', $key);
            if (!isset($_REQUEST[$ename])) continue;

            $exists_value = $mod->gC($key);
            if (!is_array($_REQUEST[$ename])) {
                $value = $this->prepareCfgValueToSaveString($key, $ename, $_REQUEST[$ename], $keyData);
            } else {
                if ($keys[$ename]['datatype'] == 'arrayExt') {
                    $value = $this->prepareCfgValueToSaveArrayExt($key, $ename, $_REQUEST[$ename], $keyData);
                } else {
                    $value = $this->prepareCfgValueToSaveArray($key, $ename, $_REQUEST[$ename], $keyData);
                }
            }
            if ($value === null) continue;

            $mod->sC($value, $key);
            $i++;
        }
        if (!$i) {
            return false;
        }

        $mod_config_filename = $mod->getConfPathByName($mod->getConfFilename());

        $result = file_put_contents(
            $mod_config_filename,
            '<?php return ' . var_export($mod->gC(), true) . '; ?>'
            , LOCK_EX);


        return $result;
    }

    function getCfgFormId($mod)
    {
        return 'cfg_form' . get_class($mod);
    }

    function prepareCfgValueToSaveString($key, $ename, $value, $keyData)
    {
        if ($value === null) {
            return '';
        }

        $value = $this->normalizeCfgValue($value, $keyData);

        return $value;
    }

    function prepareCfgValueToSaveArray($key, $ename, $value, $keyData)
    {
        if ($value === null || !is_array($value)) {
            return array();
        }

        $r = array();
        foreach ($value as $idx => $pair) {
            $vcfg = isset($keyData['array']['value']) && count($keyData['array']['value']) ? $keyData['array']['value'] : array();
            $kcfg = isset($keyData['array']['key']) && count($keyData['array']['key']) ? $keyData['array']['key'] : array();
            $vkey = $this->normalizeCfgValue($pair['key'], $kcfg);
            $value = $this->normalizeCfgValue($pair['value'], $vcfg);
            if (!$vkey || $value === null) continue;
            $r[$vkey] = $value;
        }
        return $r;
    }

    function prepareCfgValueToSaveArrayExt($key, $ename, $value, $keyData)
    {
        if ($value === null || !is_array($value)) {
            return array();
        }

        $r = array();

        $kcfg = isset($keyData['array'][0]) && count($keyData['array'][0])
            ? $keyData['array'][0]
            : array();
        $valuesCfg = $keyData['array'];
        array_shift($valuesCfg);
        foreach ($value as $citem) {
            $keyValue = $this->normalizeCfgValue($citem['key'], $kcfg);
            $r[$keyValue] = array();
            if (!isset($citem['values']) || !is_array($citem['values'])) {
                $citem['values'] = array();
            }
            foreach ($valuesCfg as $vk => $vCfg) {
                $vToHandle = isset($citem['values'][$vk])
                    ? $this->normalizeCfgValue($citem['values'][$vk], $vCfg)
                    : null;
                $r[$keyValue][$vk] = $vToHandle;
            }
        }

        return $r;
    }

    function normalizeCfgValue($value, $keyData)
    {
        if (isset($keyData['datatype']) && $keyData['datatype']) {
            $type = $keyData['datatype'];
        } else {
            if (is_numeric($value)) {
                $type = is_float($value) ? 'float' : 'integer';
            } else {
                $type = 'string';
            }
        }
        switch ($type) {
            case 'float':
                $handler = 'float';
                break;
            case 'integer':
            case 'int':
                $handler = 'integer';
                break;
            case 'bool':
            case 'boolean':
                $handler = 'boolean';
                break;
            case 'string':
            case 'text':
            default:
                $handler = 'default';
        }
        $methodName = 'cfg_handle_' . ucfirst($handler);
        $args = array($value);
        if (array_key_exists('args', $keyData) && is_array($keyData['args'])) {
            $args = array_merge($args, $keyData['args']);
        }
        $r = call_user_func_array(array($this, $methodName), $args);

        return $r;
    }

    function cfg_handle_float($value, $strict = true, $precision = 2)
    {
        $value = \Verba\reductionToFloat($value, $strict, $precision);
        return $value;
    }

    function cfg_handle_integer($value)
    {
        return (int)$value;
    }

    function cfg_handle_boolean($value)
    {
        return (bool)$value;
    }

    function cfg_handle_default($value)
    {
        return (string)$value;
    }
}
