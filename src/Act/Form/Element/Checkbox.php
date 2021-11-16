<?php
namespace Verba\Act\Form\Element;

use \Html\Checkbox as HtmlCheckbox;

class Checkbox extends HtmlCheckbox
{
  public $templates = array(
    'option' => 'aef/fe/checkbox/box.tpl'
  );

  function _init(){
    $this->listen('getValues', 'loadValues', $this);
    $this->listen('getValue', 'selectedValue', $this);
  }

  function loadValues(){
    if($this->values !== null){
      return $this->values;
    }

    $values = $this->A->filterValues(array(
      'right' => ($this->aef->getAction() == 'edit' ? 'u' : 'c')
    ));
    if(!is_array($values)){
      return false;
    }
    $this->setValues($values);
    return true;
  }

  function selectedValue(){
    if(!$this->getValue()
      && $this->A->getDefaultValue != false){
      $this->setValue($this->A->getDefaultValue);
    }
  }

  function makeE(){
    $this->fire('makeE');
    $this->fire('getValues');
    $values = $this->getValues();
    $exists_values = explode('#!#', $this->value);
    if(is_array($values) && count($values) > 0){
      $this->fire('getValue');
      $this->fire('addClasses');
      $this->fire('addEvents');
      $this->tpl = $this->tpl;
      $this->tpl->define('radio_option', $this->templates['option']);
      $this->tpl->clear_vars('RADIO_SET');
      $this->tpl->assign(array(
        'CLASSES' => $this->makeClassesTagAttr(),
        'EVENTS' => $this->makeEventsTagAttr(),
        'DISABLED' =>  $this->makeDisabled()));
      $element_id = $this->getId();
      foreach($values as $value => $text){
        $this->tpl->assign(array(
          'CHECKED' => in_array($value, $exists_values) ? 'checked' : '',
          'RADIO_VALUE' => $value,
          'RADIO_ID' => $element_id.'['.$value.']',
          'TEXT' => htmlspecialchars($text)
        ));
        $this->tpl->parse('RADIO_SET','radio_option', true);
      }
      $this->setE($this->tpl->getVar('RADIO_SET'));
    }

    $this->fire('makeEFinalize');
  }
}
