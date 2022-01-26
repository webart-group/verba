<?php

namespace Verba\Act\Form\Element\Extension;

use \Verba\Act\Form\Element\Extension;

class GeoCity extends Extension
{
  public $countryAttrCode = 'country';
  public $countryAttrId;
  public $countryBox;
  public $countryParticle;

  function engage(){
    $this->fe->listen('prepare', 'getCountryBox', $this);
    $this->fe->listen('prepare', 'addEventToCountryElement', $this);
    $this->fe->listen('loadValuesBefore', 'loadValuesByCountry', $this);
    $this->fe->listen('getCacheFilename', 'changeCacheFilename', $this, 'getCacheFilename');
    $this->fe->listen('addScripts', 'addRemoteLoadCitiesScripts', $this);
  }

  function getCountryBox(){
    if((is_numeric($this->countryAttrId = $this->ah()->oh()->code2id($this->countryAttrCode))
      && is_object($this->countryBox = $this->ah()->getAefByAttr($this->countryAttrId))
      && is_object($this->countryParticle = $this->countryBox->getParticle(0)))
    ){
      return true;
    }else{
      return false;
    }
  }

  function addEventToCountryElement(){

    $countryAttrId = $this->ah()->oh()->A($this->countryAttrCode)->getID();
    if(is_numeric($this->countryAttrId)
      && is_object($countryParticle = $this->ah->getAefByAttr($this->countryAttrId))
    ){
      $this->ah()->addJsAfter(
        'if($("#'.$countryParticle->getId().'").get(0)){'.
        "$('#".$countryParticle->getId()."').get(0).geo_location_selector = new LocationSelector('".$countryParticle->getId()."', '".$this->fe()->getId()."', '".$this->ah()->getOTID()."','".$this->fe()->A->getID()."')
      }");
      if($this->ah()->getAction() == 'new' && $countryParticle->getValue()){
        $this->ah()->addJsAfter("$('#".$countryParticle->getId()."').get(0).geo_location_selector.refreshSecondary('".$countryParticle->getValue()."');");
      }
    }
  }

  function loadValuesByCountry(){
    global $S;
    $pd = array();


    if($this->ah()->getAction() == 'edit'){
      $countryIID = is_object($this->countryParticle) ? $this->countryParticle->getValue() : false;

      if($countryIID){
        $pred_ot_id = $S->otCodeToId('predefined');
        $branch = \Verba\Branch::get_branch(array($pred_ot_id => array('iids' => array($countryIID), 'aot' => array($pred_ot_id))));
        $pd = $this->fe->A->filterValues(array(
          'ids' => $branch['pare'][$pred_ot_id][$countryIID][$pred_ot_id]
        ));
      }
    }
    if(is_array($pd)){
      $this->fe()->setValues($pd);
    }
  }

  function changeCacheFilename(){
    $cache_additional_suff = $this->countryParticle && $this->countryParticle->getValue() != false
      ? $this->countryParticle->getValue()
      : 'unknCountry';
    $cFilename = "select_{$this->ah()->getOTID()}_{$this->fe()->A->getID()}_$cache_additional_suff";
    if(is_object($cacher = $this->fe()->getExtension('cache_pd'))){
      $cacher->setFilename($cFilename);
    }
    if(is_object($cacher = $this->fe()->getExtension('cache_options'))){
      $cacher->setFilename($cFilename);
    }
    $this->fe->unlisten('getCacheFilename', 'getCacheFilename');
  }

  function addRemoteLoadCitiesScripts(){
    $this->ah()->addScripts('location-selector', 'form/e');
  }
}
