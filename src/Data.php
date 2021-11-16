<?php
namespace Verba;

class Data
{
    public $type;
    public $value;
    public $errors = array();
    public $errCodes = array();
    public $langKey = 'TFormaters';
    public $min;
    public $max;

    function __construct($cfg = null)
    {
        if (isset($cfg)) {
            $this->applyConfig($cfg);
        }
        //$this->regErrCodes(array('errors_encounter' => 'errors_encounter'));
    }

    static function getValidator($type, $cfg = null){
        $type = ucfirst(strtolower($type));
        $className = '\Data\\'
            .($type == 'String' || $type == 'Float' ? '_' : '')
            .$type;

        if(!class_exists($className)){
            return false;
        }
        return new $className($cfg);
    }

    function applyConfig($cfg)
    {
        if (!is_array($cfg) || empty($cfg)) return false;
        foreach ($cfg as $prop => $value) {
            $mtd = 'set' . ucfirst($prop);
            if (!method_exists($this, $mtd)) {
                continue;
            }

            $this->$mtd($value);
        }
        return null;
    }

    function setMin($val)
    {
        if (is_numeric($val))
            $this->min = (int)$val;
    }

    function getMin()
    {
        return $this->value;
    }

    function setMax($val)
    {
        if (is_numeric($val))
            $this->max = (int)$val;
    }

    function getMax()
    {
        return $this->max;
    }

    function getCmpConfProps()
    {
        $r = [];

        if (isset($this->min)){
            $r['min'] = $this->min;
        }
        if (isset($this->max)){
            $r['max'] = $this->max;
        }
        if ($this->value){
            $r['value'] = $this->value;

        }
        return $r;
    }

    function regErrCodes($errs)
    {
        if (is_array($errs))
            $this->errCodes = array_merge($this->errCodes, $errs);
    }

    function error($errCode, $args = null)
    {
        $this->errors[] =  \Verba\Lang::get($this->langKey . ' ' . $this->errCodes[$errCode], $args);
    }

    function getErrorsAsString()
    {
        if (!count($this->errors)) {
            return '';
        }
        return implode("\n", $this->errors);
    }

    function setValue($val)
    {
        $this->value = $val;
    }

    function getValue()
    {
        return $this->value;
    }

    function getType()
    {
        return $this->type;
    }

    function clearErrors()
    {
        $this->errors = array();
    }

    static function getAsCmpConf($vldObj, $eId, $events = false)
    {
        if (!($vldObj instanceof Data) || !is_string($eId) || empty($eId)) {
            return false;
        }
        $obj = new stdClass();
        $obj->ctype = $vldObj->getType();
        $obj->elementId = $eId;
        if (property_exists($vldObj, 'langKey') && is_string($vldObj->langKey))
            $obj->langKey = $vldObj->langKey;

        foreach ($vldObj->getCmpConfProps() as $pKey => $pValue) {
            if ($pValue !== null)
                $obj->$pKey = $pValue;
        }
        return \json_encode($obj);
    }
}

















