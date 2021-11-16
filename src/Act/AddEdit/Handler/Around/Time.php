<?php
//unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class Time extends Around
{
  function run()
  {
    if(!isset($this->value)){
      return null;
    }
    $this->value = trim($this->value);
    if(preg_match('/\d{1,2}\:\d{1,2}\:\d{1,2}/',$this->value)){
      return $this->value;
    }elseif(preg_match('/\d{1,2}\:\d{1,2}/',$this->value)){
      return '00:'.$this->value;
    }elseif(preg_match('/\d{1,2}/', $this->value)){
      return $this->value = '00:00:'.$this->value;
    }
    return false;
  }
}
