<?php

namespace Verba\Act\Form\Element;

use \Html\Element;

class Paysys extends Element
{

  public $selector_config = array();

  function makeE(){
    $this->fire('makeE');

    $paysysId = is_numeric($this->value) ? intval($this->value) : true;

    $currencyId = $this->aef->getExistsValue('currencyId');
    $currencyId = is_numeric($currencyId) ? intval($currencyId) : true;

    $cfg = array(
      'renderToSelector' => '#'.$this->aef->getFormWrapId().' .multi-parent-selector-area',
      'saveToSelector' => false,
      'saveUnits' => 'all',
      'units' => array(
        'name' => 'paysys',
        'currentValue' => $paysysId,
        'eName' => $this->getName(), //'parent['._oh('paysys')->getID().']'
        //'url' => '/dircon/catalog/getcatalog',
        'emptyOptionAllowed' => false,
        'valuesGenerator' => array(
          'type' => 'mtd',
          'handler' => array($this, 'getPaysysForMultiSelector'),
        ),
        'children' => array(
          'name' => 'currency',
          'currentValue' => $currencyId,
          'eName' => 'NewObject['.$this->aef->oh->getID().'][currencyId]',//'parent['._oh('currency')->getID().']',
          //'url' => '/acp/h/paysysadmin/getcurrenciesbypaysys/',
          'emptyOptionAllowed' => false,
          'valuesGenerator' => array(
            'type' => 'mtd',
            'handler' => array($this, 'getCurrenciesForMultiSelector'),
            'args' => is_numeric($paysysId) ? array($paysysId) : null,
          ),
        )
      )
    );

    if(is_array($this->selector_config) && count($this->selector_config) > 0){
      $cfg = array_replace_recursive($cfg, $this->selector_config);
    }

    $mpselector = new \MultiSelector(false, $cfg);
    $this->aef->addScripts($mpselector->scripts);
    $mpselector->scripts = array();
    $e = $mpselector->build();

    $this->setE($e);

    $this->fire('makeEFinalize');
  }

  function getPaysysForMultiSelector(){
    $pays = \Verba\_mod('payment')->getPaysys(null, true);
    if(!$pays){
      return false;
    }
    $r = array();
    foreach($pays as $pid => $pobj){
      $r[$pid] = $pobj->getTitle();
    }
    return $r;
  }

  function getCurrenciesForMultiSelector($paysysId = false){
    if(!$paysysId){
      return false;
    }

    $paysystem = \Verba\_mod('payment')->getPaysys($paysysId, true);
    if(!$paysystem){
      return false;
    }
    $r = array();
    foreach($paysystem->getCurrency() as $curId => $curData){
      $r[$curId] = $curData->p('title');
    }
    return $r;
  }
}
