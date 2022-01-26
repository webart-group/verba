<?php

namespace Verba\Act\Form\Element\Extension;

use \Verba\Act\Form\Element\Extension;

class OAttributeDataTypeValues extends Extension
{
  function engage(){
    $this->fe->listen('loadValuesBefore', 'loadValues', $this);
  }

  function loadValues(){
    $dt = \Mod\Otype::getInstance()->gC('avaibleDataTypes');
    $translate = \Verba\Lang::get('oattribute avaibleDataTypes');
    $r = array();
    foreach($dt as $code => $cfg){
      $r[$code] = array_key_exists($code ,$translate)
        ? $translate[$code]
        : $code;
    }
    $this->fe()->setValues($r);
  }
}
