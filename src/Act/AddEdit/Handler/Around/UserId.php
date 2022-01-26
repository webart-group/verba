<?php
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class UserId extends Around
{
  function run(){
    $this->value = (int)$this->value;
    $id = (!$this->value ? \Verba\User()->getID() : $this->value);
    return $id > 0 ? $id : 0;
  }
}
