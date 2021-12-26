<?php

namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class Logic extends Around
{
  function run(){
    if($this->value === null){
      return null;
    }
    $val = \Verba\Data\Boolean::toBool($this->value);
    return is_bool($val) ? (int)$val : $this->value;
  }
}
