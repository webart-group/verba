<?php
namespace Verba\Act\MakeList\Worker;

use \Verba\Act\MakeList\Worker;

class FinstatStaffCurrencySelector extends Worker {

  public $values = array();
  public $jsScriptFile = 'acp/FinstatStaffCurrencySelector';

  function init(){
    $this->parent->listen('queryExecuted', 'generateSelector', $this, 'generateSelectorSS');
  }

  function generateSelector(){

    $allps = \Verba\_mod('payment')->getPaysys();

    foreach($allps as $ps){
      if(!$ps->active){
        continue;
      }
      foreach($ps->currencies as $cid => $cur){
        if(!$cur['active']){
          continue;
        }
        $this->values['i'.$cid] = array(
          'rate' => $cur['rate'],
          'title' => $ps->title.' '.$cur['title'],
          'base' => $cur['isBase'],
        );
      }
    }
    $this->values;
  }

}
