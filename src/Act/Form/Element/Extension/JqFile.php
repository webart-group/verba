<?php

namespace Verba\Act\Form\Element\Extension;

use \Verba\Act\Form\Element\Extension;

class JqFile extends Extension
{
  public $feConfigType = 'file';

  function engage(){
    $this->fe->_confType = $this->feConfigType;
  }
}
