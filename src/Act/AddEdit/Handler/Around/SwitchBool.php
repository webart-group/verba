<?php
//unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class SwitchBool extends Around
{
  function run()
  {
    if($this->value != -1){
      return $this->value;
    }
    $existsValue = $this->getExistsValue($this->A->getCode());
    $r = (int)!($existsValue);
    $values = \Verba\Data\Boolean::getValues();
    $this->ah->addExtendedData([
      $this->A->getCode().'__value' => $values[$r]
    ]);
    return $r;
  }
}
