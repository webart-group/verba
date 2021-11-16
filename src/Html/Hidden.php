<?php
namespace Verba\Html;

class Hidden extends Input{
  public $type = 'hidden';

  function makeE(){
    $this->fire('makeE');
    $tag = $this->getTag();

    $this->setE('<'.$tag .' value="'.$this->getValue().'"'.$this->prepareEAttrsImploded().'/>');
    $this->fire('makeEFinalize');
  }
}
