<?php
namespace Verba\Html;

class Checkbox extends Radio
{
  public $type = 'checkbox';
  public $values;
  public $options;

  function setValues($values){
    if($values === false){
      $this->values = array();
    }
    if(is_array($values)){
      $this->values = $values;
    }
    return $this->values;
  }
  function getValues(){
    return $this->values;
  }

  function makeE(){
    $this->fire('makeE');

    $dataAttr = $this->makeAttrs();
    $ia = array();
    $ia['disabled'] = $this->makeDisabled();
    $ia['readonly'] = $this->makeReadonly();
    $this->fire('addClasses');
    $this->fire('addEvents');
    $ia['classes'] = $this->makeClassesTagAttr();
    $ia['events'] = $this->makeEventsTagAttr();

    $ia = self::implodeTagAttrs($ia);

    $options = $this->getOptions();
    $values = $this->getValues();
    if(!is_array($values)){
      $values = array();
    }
    if(is_array($options) && count($options) > 0){

      $name_base = $this->getName();
      $id_base = $this->getId();

      $inputs = '';
      foreach($options as $option_value => $text){

        $checked = !empty($this->values) && in_array($option_value, $this->values)
          ? 'checked'
          : '';

        if(count($options) > 1){
          $option_name = $name_base.'['.$option_value.']';
          $option_id = $id_base.'_'.$option_value;
        }else{
          $option_name = $name_base;
          $option_id = $id_base;
        }
        if(!empty($text)){
          $label = '<label for="'.$option_id.'">'.htmlspecialchars($text, ENT_QUOTES, 'utf-8').'</label>';
        }else{
          $label = '';
        }
        $inputs .= "\n".'<input type="'.$this->type.'" '
          . $dataAttr
          . ' name="'.$option_name.'"'
          . ' id="'.$option_id.'"'
          . ' value="'.$option_value.'" '
          . $checked
          . $ia .'/>'.$label;
      }
      $this->setE($inputs);
    }
    $this->fire('makeEFinalize');
  }
}
