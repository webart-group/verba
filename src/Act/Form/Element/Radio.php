<?php
namespace Verba\Act\Form\Element;

use \Html\Radio as HtmlRadio;

class Radio extends HtmlRadio
{
  public $templates = array(
    'option' => 'aef/fe/radio/option.tpl'
  );

  function _init(){
    $this->listen('getValues', 'loadValues', $this);
    $this->listen('getValue', 'selectedValue', $this);
  }

  function loadValues(){

    if(is_array($pd = $this->A->filterValues(array(
      'right' => $this->aef()->getAction() == 'edit' ? 'u' : 'c'
    )))){
      $this->setValues($pd);
      return true;
    }

    return false;
  }

  function selectedValue(){
    if(!$this->getValue() && $this->A->getDefaultValue() != false){
      $this->setValue($this->A->getDefaultValue());
    }

  }

  function makeE(){
    $this->fire('makeE');
    $this->fire('getValues');
    $values = $this->getValues();
    if(is_array($values) && count($values) > 0){
      $this->fire('getValue');

      $this->tpl->define('radio_option', $this->templates['option']);
      $this->tpl->clear_vars('RADIO_SET');
      $this->tpl->assign(array(
        'RADIO_NAME'=> $this->getName(),
        'CLASSES' => $this->makeClassesTagAttr(),
        'EVENTS' => $this->makeEventsTagAttr(),
        'DISABLED' =>  $this->makeDisabled()));
      $element_id = $this->getId();
      foreach($values as $value => $text){
        $this->tpl->assign(array(
          'CHECKED' => $value == $this->value ? 'checked' : '',
          'RADIO_VALUE' => $value,
          'RADIO_ID' => $element_id.'_'.$value,
          'TEXT' => htmlspecialchars($text)));
        $this->tpl->parse('RADIO_SET','radio_option', true);
      }
      $this->setE($this->tpl->getVar('RADIO_SET'));
    }

    $this->fire('makeEFinalize');
  }
}
