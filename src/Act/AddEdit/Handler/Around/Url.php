<?php
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Url extends Around
{
  function run(){
    if(empty($this->value)){
      return '';
    }
    return (new \Verba\Url($this->value))->get(true);
  }
}
