<?php
namespace Verba\Html;

class Submit extends Input{

  public $type = 'submit';

  function makeE(){
    $this->fire('makeE');
    $tag = $this->getTag();

    $this->setE("<".$tag . " value=\"".$this->getValue()."\"".$this->prepareEAttrsImploded()."/>");
    $this->fire('makeEFinalize');
  }
}
