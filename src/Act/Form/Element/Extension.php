<?php

namespace Verba\Act\Form\Element;

class Extension extends \Verba\Configurable{
  public $code;
  /**
   * @var \Verba\Html\Element
   */
  public $fe;
  /**
   * @return \Verba\Act\Form
   */
  public $ah;
  /**
   * @var \FastTemplate
   */
  public $tpl;

  function __construct($fe, $conf){
    $this->code = $this->makeCode();
    $this->fe = $fe;
    $this->tpl = $fe->tpl;
    $this->ah = $this->fe->ah();
    $this->applyConfigDirect($conf);
    $this->init();
    $this->engage();
  }
  function init(){}
  function getCode(){
    return $this->code;
  }
  function makeCode(){
    return substr(get_class($this), 6);
  }
  /**
   * Возвращает объект FormElement к которому прицеплен экстеншен.
   * @return FormElement
   */
  function fe(){
    return $this->fe;
  }
  /**
   * Возвращает текущий объект АддЭдитФормы.
   * @return \Verba\ActionHandler
   */
  function ah(){
    return $this->fe->ah();
  }
  /**
   * @return FastTemplate
   */
  function tpl(){
    return $this->tpl;
  }
  function engage(){}

  function setTemplates($tpl){
    if(!property_exists($this, 'templates') || !is_array($tpl)) return false;
    $this->templates = array_replace_recursive($this->templates, $tpl);
  }
  function getTemplates(){
    return property_exists($this, 'templates') ? $this->templates : null;
  }
}
