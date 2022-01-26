<?php

namespace Verba\Act\Form\Element\Extension;

use \Verba\Act\Form\Element\Extension;

class UrlAlias extends Extension{

  function __construct($fe, $conf){
    parent::__construct($fe, $conf);
  }

  function engage(){
    $this->fe->listen('makeE', 'loadValue', $this);
  }

  function loadValue(){
    $value = $this->fe->getValue();
    if(!$value) return false;
    $value = array_pop(explode('/', rtrim($value, '/')));
    $this->fe()->setValue($value);
  }
}
