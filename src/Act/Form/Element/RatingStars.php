<?php

namespace Verba\Act\Form\Element;

class RatingStars extends Select
{
  public $templates = array(
    'content' => 'aef/fe/ratingstars/content.tpl',
    'star' => 'aef/fe/ratingstars/star.tpl',
  );

  public $grade;

  function setGrade($val){
    $this->grade = intval($val);
  }

  function makeE(){
    parent::makeE();

    $values  = $this->getValues();
    $jsCfg = array(
      'grade' => $this->grade,
      'value' => $this->getValue(),
      'values' => $values,
    );


    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);

    $this->tpl->parse('RS_STAR','star');

    //    $inp = new AEF_Hidden($this->fe()->asCfg(), false, $this->A(), $this->aef());

    $this->tpl->assign(array(
      'RS_E_ID' => $this->fe()->getId(),
      'RS_INPUT' => $this->getE(),
      'RS_JS_CFG' => json_encode($jsCfg, JSON_FORCE_OBJECT),
    ));

    $this->setE($this->tpl->parse(false, 'content'));
    $this->fire('makeEFinalize');
  }
}
