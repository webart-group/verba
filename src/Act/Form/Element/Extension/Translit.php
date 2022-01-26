<?php

namespace Verba\Act\Form\Element\Extension;

use \Verba\Act\Form\Element\Extension;

class Translit extends Extension
{
  public $sourceAttrCode = null;
  public $lang = null;
  public $srcA = null;

  function engage(){

    $aths = $this->fe->A()->getHandlers('ae');
    if(is_array($aths)){
      foreach($aths as $ath){
        if($ath['ah_name'] != 'translit'){
          continue;
        }
        $p = $ath['params'];
        break;
      }
    }

    if(!isset($this->lang) && is_array($p) && isset($p['lang'])){
      $this->lang = $p['lang'];
    }else{
      $this->lang = \Verba\Lang::getDefaultLC();
    }
    if(!isset($this->sourceAttrCode) && is_array($p) && isset($p['secondary'])){
      $srcCode = $p['secondary'];
    }else{
      $srcCode = 'title';
    }

    $this->srcA = $this->fe->oh->A($srcCode);
    if(!$this->srcA){
      return false;
    }
    $this->sourceAttrCode = $this->srcA->getCode();

    $this->fe->listen('prepare', 'hookScripts', $this);
  }

  function hookScripts(){
    $cfg = array(
      'ot_id' => $this->ah()->getOTID(),
      'form_id' => $this->ah()->getFormId(),
      'sourceAttrCode' => $this->sourceAttrCode,
      'lang' => $this->lang,
      'thisAttrCode' => $this->fe->A()->getCode(),
      'multilang' => $this->srcA->isLcd(),
    );

    $this->ah()->addJsAfter("$(document).ready(function(){var tr = new translitBySourceField(".json_encode($cfg).");});");
  }
}
