<?php

namespace Verba\Act\Form\Element\Extension;

use Act\Form\Element\Extension;

class ExtOptionAttrs extends Extension
{
  public $attrs = array();
  public $extOptionOt;

  function engage(){
    $this->fe->listen('prepare', 'loadExtOptionAttrs',$this, 'loadExtOptionAttrs');
  }

  function loadExtOptionAttrs(){

    $oh = (!isset($this->extOptionOt)) ? $this->fe->oh : \Verba\_oh($this->extOptionOt);
    $qm = new \Verba\QueryMaker($oh, false, $this->attrs);
    if($oh->isA('active')){
      $qm->addWhere(1,'active');
    }
    $sqlr = $qm->run();
    if(!$sqlr){
      return false;
    }
    $r = array();
    $pac = $oh->getPAC();
    while($row = $sqlr->fetchRow()){
      foreach($this->attrs as $code){
        $this->fe->setExtOptionAttr($row[$pac], 'data-'.$code, $row[$code]);
      }
    }
  }
}
