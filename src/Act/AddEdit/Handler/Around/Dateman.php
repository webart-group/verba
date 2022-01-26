<?php
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Dateman extends Around
{
  function run()
  {
    if(isset($this->value) && !empty($this->value)){
      $this->value = strtotime($this->value);
    }
    if(!isset($this->value) || !$this->value){
      if($this->action == 'new'){
        $this->value = time();
      }else{
        return null;
      }
    }

    $format = $this->A->data_type == 'date'
      ? 'Y-m-d'
      : 'Y-m-d H:i:s';
    $r = date($format, $this->value);
    return $r;
  }
}
