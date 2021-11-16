<?php
namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class Datetime extends Around
{
  function run(){
    if(!isset($this->value)){
      return null;
    }

    if($this->A->getDataType() === 'date'){
      $format = 'Y-m-d'; $default = '0000-00-00';
    }else{
      $format = 'Y-m-d H:i:s'; $default = '0000-00-00 00:00:00';
    }

    return  is_numeric($result = strtotime($this->value))
      ? date($format, $result)
      : $default;
  }
}
