<?php
//unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class Money extends Around
{
  function run()
  {
    return $this->value === null
      ? null
      : \Verba\reductionToCurrency($this->value);
  }
}
