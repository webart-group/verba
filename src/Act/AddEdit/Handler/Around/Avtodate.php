<?php
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Avtodate extends Around
{
  function run()
  {
    $forced = $this->ah->getExtendedData('_forced');
    if(isset($forced) && isset($forced[$this->A->getCode()])){
      return $forced[$this->A->getCode()];
    }
    return date('Y-m-d H:i:s');
  }
}
