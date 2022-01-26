<?php
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Autoincrement extends Around
{
  function run()
  {
//    if($this->action == 'new' && null !== ($gvalue = $this->ah->getGettedValue('id'))){
//      return $gvalue;
//    }
    return null;
  }
}
