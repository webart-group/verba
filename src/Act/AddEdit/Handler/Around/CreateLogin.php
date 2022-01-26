<?php
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class CreateLogin extends Around
{
  function run()
  {
    if(empty($this->ah->getGettedValue('login'))){
      return md5($this->ah->getGettedValue('email'));
    }
    return $this->ah->getGettedValue('login');
  }
}
