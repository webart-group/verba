<?php
namespace Verba\Act\AddEdit\Handler;

use Act\AddEdit\Handler;

class Around extends Handler{

  /**
   * @var string locale code
   */
  protected $lc;
  protected $__exists_value = '$!$~~$!$';
  protected $set_data;
  protected $params = [];

  function setA($A)
  {
    $this->A = $A;
  }

  function getA()
  {
    return $this->A;
  }

  function setLc($lc)
  {
    $this->lc = $lc;
  }

  function getLc()
  {
    return $this->lc;
  }

  function getExistsValue($attr_code){
    if($this->__exists_value === '$!$~~$!$')
    {
      $this->__exists_value = $this->ah->getExistsValue($attr_code);
    }

    return $this->__exists_value;
  }

  function setSet_data($val)
  {
    if(array_key_exists('params', $val)) {
      $this->params = $val['params'];
      unset($val['params']);
    }
    $this->set_data = $val;
  }
}

