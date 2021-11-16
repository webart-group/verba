<?php

namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class Html2entities extends Around
{
  function run()
  {
    if(!isset($this->value)){
      return null;
    }
    return htmlentities($this->value, ENT_QUOTES, 'utf-8');
  }
}
