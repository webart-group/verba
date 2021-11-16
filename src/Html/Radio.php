<?php
namespace Verba\Html;

class Radio extends Input
{
  public $type = 'radio';
  public $options;

  function setOptions($values){
    if($values === false){
      $this->options = array();
    }
    if(is_array($values)){
      $this->options = $values;
    }
    return $this->options;
  }
  function getOptions(){
    return $this->options;
  }

  function makeE(){
    $this->fire('makeE');
    $values = $this->getValues(); // ???? где метод?
    if(is_array($values) && count($values) > 0){
      $element_id = $this->getId();
      $name = $this->getName();
      $dataAttr = $this->makeAttrs();
      $ia = array();
      $ia['disabled'] = $this->makeDisabled();
      $this->fire('addClasses');
      $this->fire('addEvents');
      $ia['classes'] = $this->makeClassesTagAttr();
      $ia['events'] = $this->makeEventsTagAttr();
      $set = '';
      $ia = self::implodeTagAttrs($ia);
      foreach($values as $value => $text){
        $id = $element_id.'_'.$value;
        $checked = $value == $this->value ? 'checked' : '';
        $set .= "\n<input".$dataAttr." type=\"radio\" name=\"$name\" id=\"$id\" value=\"$value\" $checked $ia/> ".htmlspecialchars($text);
      }
      $this->setE($set);
    }
    $this->fire('makeEFinalize');
  }

  function exportAsCfg(){
    $r = parent::exportAsCfg();
    if(is_array($this->getValues())) $r['values'] = $this->getValues();
    return $r;
  }
}
