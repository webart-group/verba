<?php
//unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class Md5 extends Around
{
  function run(){
    if(!isset($this->params['secondary'])
      || !is_object($Av = $this->oh->A($this->params['secondary']))
      || !isset($this->gettedObjectData[$Av->getCode()])
    ){
      return false;
    }

    return md5($this->ah->getGettedValue($Av->getCode()));
  }
}
