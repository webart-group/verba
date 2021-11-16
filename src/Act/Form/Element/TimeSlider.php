<?php

namespace Verba\Act\Form\Element;

use \Html\Element;

class TimeSlider extends Element
{
  public $templates = array(
    'body' => 'aef/fe/timeslider/fe.tpl',
  );
  protected $cfg = array(
    'range' => false,
    'min' => false,
    'max' => false,
    'values'=> array(),
  );

  function setCfg($val){
    if(!is_array($val)){
      return false;
    }
    $this->cfg = array_replace_recursive($this->cfg, $val);
  }

  function makeE(){
    $this->fire('makeE');

    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);

    //dateInput
    $input = new \Html\Text(parent::exportAsCfg());

    if(is_string($this->getValue()) && !is_numeric($this->getValue())){
      $input->setValue($this->getValue());
    }

    $this->tpl->assign(array(
      'FE_ID' => $input->getId(),
      'FE_CFG' => json_encode($this->cfg),
    ));

    $this->tpl->assign(array(
      'FE' => $input->build()
    ));

    $this->setE($this->tpl->parse(false, 'body'));
    $this->fire('makeEFinalize');
  }
}
