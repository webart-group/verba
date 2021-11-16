<?php

namespace Verba\Act\Form\Element\Extension;

use Act\Form\Element\Extension;

class Cache extends Extension
{
  protected $cache;
  protected $cachefile_end = '';
  static protected $cacheFEPropNameBase = '_cache';
  protected $cacheFEPropName;
  protected $cacheFEPropSuffix;

  function __construct($fe, $conf){
    $this->code = $this->makeCode();
    $this->fe = $fe;
    $this->cacheFEPropName = is_string($this->cacheFEPropSuffix) ? self::$cacheFEPropNameBase.$this->cacheFEPropSuffix : self::$cacheFEPropNameBase;
    $fe->{$this->cacheFEPropName} = array('subway'   => null,'filename'=> null, 'allowed' => true);

    $this->applyConfig($conf);

    if(!is_string($fe->{$this->cacheFEPropName}['filename'])){
      $this->setFilename($this->makeDefaultFilename());
    }
    if(!is_string($fe->{$this->cacheFEPropName}['subway'])){
      $this->setSubway($this->makeDefaultSubway());
    }

    $this->engage();
  }
  function __call($mth, $args){
    if($this->cache === false || !is_object($this->cache) && !$this->makeCacheInstance()){
      return false;
    }
    if(!is_callable(array($this->cache, $mth))){
      throw new Exception('Undefined class method called: '.__CLASS__.'::'.$mth.'()');
    }
    return call_user_func_array(array($this->cache, $mth), $args);
  }

  function getCacheFEPropName(){
    return $this->cacheFEPropName;
  }

  function setAllowed($state){
    $this->fe()->{$this->cacheFEPropName}['allowed'] = (bool)$state;
  }
  function getAllowed(){
    return $this->fe()->{$this->cacheFEPropName}['allowed'];
  }

  function setFilename($filename){
    if(is_string($filename)){
      $this->fe()->{$this->cacheFEPropName}['filename'] = $filename.$this->cachefile_end;
    }
  }
  function getFilename(){
    return $this->fe()->{$this->cacheFEPropName}['filename'];
  }
  function makeDefaultFilename(){
    return "{$this->fe()->getTag()}_{$this->ah()->getOTID()}_{$this->fe()->acode}";
  }

  function setSubway($sway){
    if(is_string($sway)){
      $this->fe()->{$this->cacheFEPropName}['subway'] = $sway;
    }
  }
  function getSubway(){
    return $this->fe()->{$this->cacheFEPropName}['subway'];
  }
  function makeDefaultSubway(){
    return "{$this->fe()->getTag()}";
  }
  function cache(){
    return is_object($this->cache) || $this->makeCacheInstance()
      ? $this->cache
      : false;
  }

  function makeCacheInstance(){
    $fe = $this->fe();
    $fe->fire('getCacheSubway');
    $fe->fire('getCacheFilename');
    if(!$fe->{$this->cacheFEPropName}['allowed'] || !is_string($fn = $this->getFilename())){
      $this->cache = false;
      return $this->cache;
    }

    $this->cache = new \Verba\Cache($this->getSubway().'/'.$fn);
    return true;
  }
}
