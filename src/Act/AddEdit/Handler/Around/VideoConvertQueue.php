<?php
//unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class VideoConvertQueue extends Around
{
  function run()
  {
    $attr_code = $this->A->getCode();
    if(!isset($cValue)){
      return null;
    }
    $value = (int)$cValue;

    if($this->ah()->getIid() > 0 && $this->value == 1){
      \Verba\_mod('video')->addToConvertQueue($this->ah()->getIid());
    }
    return $value;
  }
}
