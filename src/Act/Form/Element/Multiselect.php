<?php
namespace Verba\Act\Form\Element;

class Multiselect extends Select
{
  public $multiple = true;

  function setValue($val){
    $this->value = is_string($val) && !empty($val)
      ? explode(',',$val)
      : array();
  }

  function makeNameTagAttr(){
    return is_string($this->name) ? 'name="'.$this->name.'[]"' : '';
  }
}
