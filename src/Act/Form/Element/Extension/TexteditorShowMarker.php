<?php

namespace Verba\Act\Form\Element\Extension;

use Act\Form\Element\Extension;

class TexteditorShowMarker extends Extension
{
  public $marker = '+++';

  function engage(){
    $this->fe->listen('prepare', 'loadValue', $this);
  }

  function loadValue(){

    if($this->isLcd){
      $lcs = \Verba\Lang::getUsedLC();
    }else{
      $lcs = array(0);
    }
    foreach($lcs as $lc){
      if($this->isLcd){
        $this->fe->setLocale($lc);
      }
      $value = $this->fe->getValue();
      $value = str_replace('<!--'.$this->marker.'-->', $this->marker, $value);
      $this->fe->setValue($value);
    }
  }
}
