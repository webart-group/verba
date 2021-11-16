<?php

namespace Verba\Act\Form\Element\Extension;

use Act\Form\Element\Extension;

class SecToHours extends Extension
{
  function engage(){
    $this->fe->listen('makeE', 'reductToHours', $this);
  }

  function reductToHours(){
    $val = $this->fe()->getValue();

    if(!$val || $val <= 0){
      return;
    }
    $val = ceil($val / 3600);
    $this->fe()->setValue($val);
  }
}
