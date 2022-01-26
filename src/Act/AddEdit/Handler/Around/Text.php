<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Text extends Around
{
  function run()
  {
    if(!isset($this->value)){
      return null;
    }
    return (string)$this->value;
  }
}
