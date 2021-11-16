<?php

namespace Verba\Act\Form\Element;

class DateTimeSelector_GameStartAt extends Datetimeselector
{
  protected $wrap_hidden_sign;

  function _init(){
    $this->listenPrepend('makeEFinalize', 'wrapEIntoWrapper', $this);
  }

  function makeE(){

    if($this->ah()->getAction() == 'edit'){
      if($this->value &&  (!is_numeric($ts = strtotime($this->value)) || $ts < 1) ){
        $ms_val = '0';
        $this->wrap_hidden_sign = ' hidden';
      }else{
        $this->wrap_hidden_sign = '';
        $ms_val = '1';
      }
    }else{
      $this->wrap_hidden_sign = ' hidden';
      $ms_val = '0';
    }

    $this->getEbox()->addClasses('ext-gsa');

    parent::makeE();

    $modeSelId =$this->getId().'_mode';

    $modeSel = new \Html\Select(array(
      'id' => $modeSelId,
      'values' =>  \Verba\Lang::get('game form fields gameStartAt values'),
      'classes' => array('game-start-at-mode'),
      'value' => $ms_val,
    ));

    $this->tpl->define(array(
      'full_content' => 'aef/fe/GameStartAt/content.tpl',
      'init' => 'aef/fe/GameStartAt/init.tpl',
    ));

    $this->tpl->assign(array(
      'MODE_SELECTOR' => $modeSel->build(),
      'NATIVE_E' => $this->getE(),
      'MODE_SEL_ID' => $modeSelId,
      'E_ID' => $this->getId()
    ));
    $this->setE($this->tpl->parse(false, 'full_content'));

    $this->ah()->addJsAfter($this->tpl->parse(false, 'init'));
  }

  function wrapEIntoWrapper(){
    $this->setE(
      '<div class="start-at-native-e'.$this->wrap_hidden_sign.'" id="'.$this->getId().'_ext_mdselector">'
      . $this->getE()
      . '</div>'
    );
  }
}
