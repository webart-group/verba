<?php
namespace Verba\Act\MakeList\Worker;

use \Verba\Act\MakeList\Worker;

class Selectable extends Worker{

  public $jsScriptFile = 'Selectable';
  public $attr;
  public $values;

  function init(){
    $this->parent->listen('queryExecuted', 'loadData', $this, 'Selectable_load_data');
    $this->parent->listen('fieldBefore', 'addCurrentValueToDataAttr', $this, 'Selectable_addCurrentValueToDataAttr');
  }

  function loadData(){
    if(!isset($this->attr) || !is_string($this->attr) || false == ($A = $this->parent->oh()->A($this->attr))){
      return false;
    }
    $this->values = $A->getValues();
  }

  function addCurrentValueToDataAttr(){
    if($this->parent->fieldCode != $this->attr){
      return;
    }

    if(!array_key_exists('attr', $this->parent->fieldCfg)){
      $this->parent->fieldCfg['attr'] = array();
    }

    $this->parent->fieldCfg['attr']['data-value'] = $this->parent->row[$this->attr];
  }

}
