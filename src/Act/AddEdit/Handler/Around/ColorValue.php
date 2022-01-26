<?php
//unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class ColorValue extends Around
{
  function run(){
    if(!isset($this->value)){
      return null;
    }
    if(is_string($this->value)){
      $this->value = substr($this->value, 0,1) == '#' ? substr($this->value,1) : $this->value;
    }
    return $this->value;
  }
}
