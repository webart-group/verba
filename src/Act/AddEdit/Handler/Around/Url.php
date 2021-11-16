<?php
namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class Url extends Around
{
  function run(){
    if(empty($this->value)){
      return '';
    }
    return (new \Url($this->value))->get(true);
  }
}
