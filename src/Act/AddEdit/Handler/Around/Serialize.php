<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Serialize extends Around
{
  function run()
  {
    return isset($this->value) ? serialize($this->value) : null;
  }
}
