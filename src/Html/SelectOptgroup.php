<?php
namespace Verba\Html;
class SelectOptgroup extends Element{
  public $tag = 'optgroup';
  public $values;
  public $label;

  /**
   * @param array $values значения для генерации OPTION-элементов в формате array(optionValue => optionText[, ...])
   * @return boolean
   */
  function setValues($values){
    if(!is_array($values)) return false;
    if(!is_array($this->values)){
      $this->values = $values;
    }else{
      $this->values = array_replace_recursive($this->values, $values);
    }
  }
  function getValues(){
    return $this->values;
  }

  function setLabel($val){
    if(is_string($val) && !empty($val))
      $this->label = $val;
  }
  function getLabel(){
    return $this->label;
  }
  function makeLabelTagAttr(){
    return is_string($this->label) ? 'label="'.$this->label.'"' : '';
  }
  function prepareEAttrs(){
    $ia = parent::prepareEAttrs();
    $ia['label'] = $this->makeLabelTagAttr();
    return $ia;
  }
  function makeE(){
    $this->fire('makeE');
    if(is_array($vls = $this->getValues())){
      $tag = $this->getTag();

      $this->setE("<$tag".$this->prepareEAttrsImploded().">".\Html\Select::generateOptions($vls, $this->getValue())."</$tag>");
    }
    $this->fire('makeEFinalize');
  }
}
