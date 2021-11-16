<?php
namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class UserHash extends Around
{
  function run()
  {
    $field  = $this->ah->getGettedValue('login') === null
      ? 'email'
      : 'login';
    $r = substr(md5($this->ah->getGettedValue($field)),0, 16).substr(md5(rand(1, 9999)),0, 16);
    return $r;
  }
}
