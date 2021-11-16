<?php

namespace Verba\Act\Form\Element\Extension;

use Act\Form\Element\Extension;

class ClearValue extends Extension
{
  public $clearAtNew = false;
  public $clearAtEdit = true;

  function engage(){
    $this->fe->listen('makeE', 'doClearValue', $this);
  }

  function setClearAtNew($val){
    $this->clearAtNew = (bool) $val;
  }
  function getClearAtNew(){
    return $this->clearAtNew;
  }

  function setClearAtEdit($val){
    $this->clearAtEdit = (bool) $val;
  }
  function getClearAtEdit(){
    return $this->clearAtEdit;
  }

  function doClearValue(){
    if($this->ah()->getAction() == 'edit' && $this->getClearAtEdit()
      || $this->ah()->getAction() == 'new' && $this->getClearAtNew()){
      $this->fe()->setValue(null);
    }
  }
}
