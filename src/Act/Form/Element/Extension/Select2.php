<?php

namespace Verba\Act\Form\Element\Extension;

use Act\Form\Element\Extension;

class Select2 extends Extension
{
  public $s2opts = array(
    'width' => '100%',
    'closeOnSelect' => false,
    'multiple' => false,
  );

  function engage(){
    $this->fe->listen('parseOptions', 'parseOptions', $this);
    $this->fe->listen('makeE', 'makeE', $this);
  }

  function parseOptions(){
    $vls = $this->fe->getValues();
    if(!is_array($vls) || !count($vls)) {
      return '[]';
    }
    $this->fe->options = array();
    foreach($vls as $k => $v){
      $this->fe->options[] = '{"id":"'.$k.'", "text":"'.htmlspecialchars($v).'"}';
    }

    $this->fe->options = '['.implode(',',$this->fe->options).']';

    return $this->fe->options;
  }

  function makeE(){

    $this->fe->makeOptions();

    if($this->fe->getMultiple()){
      $this->s2opts['multiple'] = true;
    }

    $tpl = $this->tpl();
    $tpl->define(array(
      'AEF_Select2_wrap' => '/aef/fe/select2/wrap.tpl',
      'AEF_Select2_selected' => '/aef/fe/select2/selected.tpl',
    ));

    $tpl->assign(array(
      'SELECT2_SELECT_E' => "<"
        . $this->fe->getTag()
        . $this->fe->prepareEAttrsImploded()
        . ">"
        . "</".$this->fe->getTag().">",
    ));

    $tpl->assign(array(
      'SELECT2_SELECT_ID' => $this->fe->getId(),
      'OPTIONS' => $this->fe->options,
      'SELECT2_OPTS' => json_encode($this->s2opts, JSON_FORCE_OBJECT),
    ));

    if($this->fe->value){
      if(is_array($this->fe->value)){
        $selected = "['".implode("', '", $this->fe->value)."']";
      }else{
        $selected = "'".$this->fe->value."'";
      }
      $tpl->assign(array(
        'SELECT2_SELECTED_VALUES' => $selected
      ));
      $tpl->parse('SELECTED_STATEMENT','AEF_Select2_selected');
    }else{
      $tpl->assign(array(
        'SELECTED_STATEMENT' => ''
      ));
    }

    $this->fe->setE($tpl->parse(false, 'AEF_Select2_wrap'));

    $this->ah()->addScripts('jselect2.min', 'jquery/select2/js');
    $this->ah()->addCSS('select2.min', 'jquery/select2/css');

    if(SYS_LOCALE !== 'en'){
      $sl = SYS_LOCALE == 'ua' ? 'uk' : SYS_LOCALE;
      $this->ah()->addScripts($sl, 'jquery/select2/js/i18n');
    }


  }
}
