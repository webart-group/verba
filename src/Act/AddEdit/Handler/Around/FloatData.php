<?php
//unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class FloatData extends Around
{
  function run()
  {
    if(!isset($this->value)){
      return null;
    }
    $this->value = (string)$this->value;
    $this->value = str_replace(',', '.', $this->value);
    return reductionToFloat($this->value);
  }
}
