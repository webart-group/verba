<?php

namespace Verba\Act\Form\Element;

use \Verba\Html\Element;

class TimeRange extends Element
{
  public $templates = array(
    'body' => 'aef/fe/timerange/range.tpl',
  );
  protected $cfg = array(
    'range' => true,
    'min' => false,
    'max' => false,
    'values'=> array(),
  );
  public $tillAttrCode = 'deliveryTime1';

  function setCfg($val){
    if(!is_array($val)){
      return false;
    }
    $this->cfg = array_replace_recursive($this->cfg, $val);
  }

  function makeE(){
    $this->fire('makeE');
    $oh = $this->aef->oh();

    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);

    //dateInput
    $input = new \Verba\Html\Text(parent::exportAsCfg());

    if(is_string($this->getValue()) && !is_numeric($this->getValue())){
      $input->setValue($this->getValue());
    }

    if(is_numeric($tillAttrId = $this->aef->oh->code2id($this->tillAttrCode))
      && is_object($tillBox = $this->aef->getAefByAttr($tillAttrId))
    ){
      $tillE = $tillBox->getParticle(0);
    }else{
      $tillE = $this;
    }

    $this->tpl->assign(array(
      'RANGE_FE_ID' => $input->getId(),
      'RANGE_TILL_FE_ID' => $tillE->getId(),
      'RANGE_DISABLED' => $input->makeDisabled(),
      'RANGE_FE_CFG' => json_encode($this->cfg),
    ));

    $this->tpl->assign(array(
      'RANGE_FE' => $input->build()
    ));

    $this->setE($this->tpl->parse(false, 'body'));
    $this->fire('makeEFinalize');
  }
}