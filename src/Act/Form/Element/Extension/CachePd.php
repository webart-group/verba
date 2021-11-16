<?php

namespace Verba\Act\Form\Element\Extension;

class CachePd extends Cache
{
  protected $cachefile_end = '_pd';
  protected $cacheFEPropSuffix = 'PdValues';

  function engage(){
    $this->fe->listen('loadValuesBefore', 'loadValuesFromCache', $this);
  }

  function loadValuesFromCache(){
    if($this->validateDataCache(300)){
      $this->fe()->setValues($this->getAsRequire());
      return true;
    }
    $this->fe->listen('loadValuesAfter', 'saveValuesToCache', $this, 'savePV2C');
    return false;
  }

  function saveValuesToCache(){
    $this->fe->unlisten('loadValuesAfter', 'savePV2C');
    if(count($this->fe()->getValues())){
      $this->writeDataToCache($this->fe()->getValues());
    }
  }
}
