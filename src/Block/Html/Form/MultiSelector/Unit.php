<?php

namespace Block\Html\Form\MultiSelector;

class Unit extends \Verba\Configurable{
    public $name;
    public $url;
    public $values; // array(pvalue => array(k => title)) Values for select groupped by parent Value
    public $currentValue; // Value of Current Select
    protected $parentValue; // Value of Parent Select
    public $eName;
    public $emptyOptionAllowed;
    public $children;
    public $urlParams;
    public $preload = false;  // current | all - if set `current` will be load
    // by currentParentValue, `all` will be loaded
    // for all parent values

    protected $parent;

    function __construct($cfg, $parent){
        if(!is_array($cfg)) return false;

        $this->parent = $parent;

        $this->parentValue = $this->parent->getCurrentValue();

        if(array_key_exists('valuesGenerator', $cfg)){
            $valGen = $cfg['valuesGenerator'];
            unset($cfg['valuesGenerator']);
        }

        if(array_key_exists('children', $cfg)){
            $children = $cfg['children'];
            unset($cfg['children']);
        }

        $this->applyConfigDirect($cfg);

        if(is_string($this->preload) && !is_array($this->values) && isset($valGen['handler']) && is_array($valGen['handler'])){

            if($this->preload == 'current'){
                $pids = array($this->parentValue);
            }elseif($this->preload == 'all'){
                $pids = $this->parent->getAllIidsForPreload();
            }
            $args = array($pids);

            if(is_array($valGen['args']) && !empty($valGen['args'])){
                $args = array_merge($args, $valGen['args']);
            }

            $h = $valGen['handler'];
            if(isset($valGen['handler'][0]) && isset($valGen['handler'][1])){
                if(!is_object($valGen['handler'][0])){
                    if(\Verba\Hive::isModExists($valGen['handler'][0])){
                        $valGen['handler'][0] = \Verba\_mod($valGen['handler'][0]);
                    }else{
                        throw new Exception('Unknown values generator. Syntax error');
                    }
                }
                $h = array(
                    $valGen['handler'][0], // mod object
                    $valGen['handler'][1] // method name
                );


                if(isset($h)){
                    $vals = call_user_func_array($h, $args);
                    $this->setValues($vals);
                    //if(!$this->currentValue || !array_key_exists($this->currentValue, $this->values)){
                    //          $this->currentValue = false;
                    //          if(is_array($this->values) && !empty($this->values)){
                    //            $this->setCurrentValue(key($this->values));
                    //          }
                    //        }
                }
            }
        }
        if(isset($children)){
            $this->setChildren($children);
        }
    }

    function getAllIidsForPreload(){
        if(!is_array($this->values) || !count($this->values)){
            return array();
        }
        $r = array();
        foreach($this->values as $parentId => $thisIds){
            if(!is_array($thisIds) || !count($thisIds)){
                continue;
            }
            $r += array_keys($thisIds);
        }
        $r = array_unique($r);
        return $r;
    }

    function setPreload($val){
        if($val === false){
            $this->preload = false;
        }elseif(is_string($val) && $val == 'current' || $val == 'all'){
            $this->preload = $val;
        }
    }
    function getPreload(){
        return $this->preload;
    }

    function setName($val){
        $this->name = (string)$val;
    }
    function getName(){
        return $this->name;
    }

    function setUrl($val){
        $this->url = (string)$val;
    }
    function getUrl(){
        return $this->url;
    }

    function getUrlParams(){
        return $this->children;
    }
    function setUrlParams($name, $val = null){
        if(is_array($name) && $val === null){
            foreach($name as $k => $v){
                $this->setUrlParams($k, $v);
            }
            return true;
        }
        if(is_string($name) || is_numeric($name)){
            $this->urlParams[$name] = $val;
        }
    }

    function setValues($val){
        $this->values = (array)$val;
    }
    function getValues(){
        return $this->values;
    }

    function setCurrentValue($val){
        $this->currentValue = is_numeric($val) ? (int)$val : (is_string($val) ? $val : false);
    }
    function getCurrentValue(){
        return $this->currentValue;
    }

    function setEName($val){
        $this->eName = (string)$val;
    }
    function getEName(){
        return $this->eName;
    }

    function setEmptyOptionAllowed($val){
        $this->emptyOptionAllowed = (bool)$val;
    }
    function getEmptyOptionAllowed(){
        return $this->emptyOptionAllowed;
    }

    function getChildren(){
        return $this->children;
    }
    function setChildren($val){
        if($val instanceof $this){
            $this->children = $val;
            return true;
        }
        if(is_array($val)){
            $this->children = new Unit($val, $this);
        }

    }
}