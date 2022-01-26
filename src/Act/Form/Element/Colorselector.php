<?php

namespace Verba\Act\Form\Element;

use \Verba\Html\Element;

class Colorselector extends Element
{
  public $templates = array(
    'body' => 'aef/fe/colorselector/colorselector.tpl',
  );
  public $userInput = true;

  function _init(){
    $this->box()->addCSS('colorpicker', SYS_JS_URL.'/jquery/plugins/colorpicker/js');
    $this->box()>addScripts('colorpicker', SYS_JS_URL.'/jquery/plugins/colorpicker/css');
  }

  function makeE(){
    $this->fire('makeE');

    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);

    $val = $this->getValue();
    if(!$val){
      $val = '000000';
    }

    //colorInput
    $input = new \Verba\Html\Text(parent::exportAsCfg());
    $this->tpl->assign(array(
      'COLORPICKER_FE_ID' => $input->getId(),
      'COLORSELECTOR_FE' => $input->build(),
      'COLORPICKER_CURRENT_VALUE' => $val,
    ));
    $this->setE($this->tpl->parse(false, 'body'));
    $this->fire('makeEFinalize');
  }
}
