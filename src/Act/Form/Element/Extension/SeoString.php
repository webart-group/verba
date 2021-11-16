<?php

namespace Verba\Act\Form\Element\Extension;

use Act\Form\Element\Extension;

class SeoString extends Extension
{
  public $sourceAttrCode = null;
  public $srcA = null;

  function engage(){
    if(!is_string($this->sourceAttrCode)){
      $aths = $this->fe->A()->getHandlers('ae');
      if(is_array($aths)){
        foreach($aths as $ath){
          if($ath['ah_name'] != 'strtoseo'){
            continue;
          }
          $srcCode = $ath['params']['secondary'];
          break;
        }
      }
    }
    if(!isset($srcCode)){
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
      'thisAttrCode' => $this->fe->A()->getCode(),
      'multilang' => $this->srcA->isLcd(),
      'defaultLang' => \Verba\Lang::getDefaultLC(),
    );

    $this->ah()->addScripts(array('convert', 'acp/aef'));
    $this->ah()->addJsAfter("$(document).ready(function(){var tr = new convertToSEOString(".json_encode($cfg).");});");
  }
}
