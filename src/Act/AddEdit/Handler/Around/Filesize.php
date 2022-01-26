<?php
//unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Filesize extends Around
{
  function run(){
    if(!empty($cValue)) return $cValue;
    if(!isset($set_data['params']['secondary'])
      || !is_object($Av = $this->oh->A($this->params['secondary']))
    ){
      return false;
    }

    $cValue = isset($this->extendedData[$Av->getCode()]['size'])
      ? $this->ah()->extendedData[$Av->getCode()]['size']
      : null;

    return $cValue;
  }
}
