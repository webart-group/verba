<?php

namespace Verba\Act\Form\Element\Extension;

class CacheOptions extends Cache
{
  protected $cachefile_end = '_options';
  protected $cacheFEPropSuffix = 'Options';

  function engage(){
    $this->fe->listen('getOptions', 'getOptionsFromCache', $this);
  }

  function getOptionsFromCache(){
    if($this->ah()->getAction() == 'new'){
      if($this->validateDataCache(300)){
        $this->fe()->options = $this->getAsRequire();
        return true;
      }
      $this->fe->listen('getOptionsFinalize', 'saveOptionsToCache', $this, 'saveO2C');
    }
    return false;
  }

  function saveOptionsToCache(){
    $this->fe->unlisten('getOptionsFinalize', 'saveO2C');
    if(!empty($this->fe()->options)){
      if($this->writeDataToCache($this->fe()->options)){
        return true;
      }
    }
    return false;
  }
}
