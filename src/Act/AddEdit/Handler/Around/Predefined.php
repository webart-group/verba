<?php
//unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Predefined extends Around
{
  function run(){
    if(!isset($this->value)){
      if($this->action == 'new'){
        $Pdset = $this->A->PdSet($this->oh);
        return is_object($Pdset) ? $Pdset->getDefaultValue() : null;
      }
      return null;
    }

    if(!$this->value){
      return $this->value;
    }

    $right = $this->action === 'edit' ? 'u' : 'c';

    $pd_data = $this->A->filterValues(array(
      'right' => $right,
      'id' => $this->value,
    ));
    if(!is_array($pd_data)){
      return false;
    }
//    if($this->oh->in_behavior('protected_predefined', $A->getID())){
//      $this->log()->error('Access denied for pd values. Attribute:['.$A->getCode() .']');
//      return false;
//    }
    $this->ah->addExtendedData([$this->A->getCode().'__value' => current($pd_data)]);
    return is_array($pd_data) ? key($pd_data) : false;
  }
}
