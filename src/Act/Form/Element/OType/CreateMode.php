<?php

namespace Verba\Act\Form\Element\OType;

use \Html\Element;

class CreateMode extends Element
{
  protected $values = null;

  function getValues(){
    if($this->values === null){
      $this->values = array();
      $this->values = $this->loadValues();
    }
    return $this->values;
  }

  function loadValues(){
    $r = array(
      'clone' => \Verba\Lang::get('otype aef createModeOptions clone'),
      'extend' => \Verba\Lang::get('otype aef createModeOptions extend'),
    );
    return $r;
  }

  function makeE(){
    $this->fire('makeE');

    $fe = new FERadio();
    $fe->setId($this->getId());
    $fe->setName($this->getName());
    $fe->setValues($this->getValues());

    $this->setE($fe->build());
    $this->fire('makeEFinalize');
  }
}