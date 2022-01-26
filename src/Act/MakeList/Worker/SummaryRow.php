<?php
namespace Verba\Act\MakeList\Worker;

use \Verba\Act\MakeList\Worker;

class SummaryRow extends Worker{
  public $attrs = array();

  public $jsScriptFile = 'SummaryRow';

  function init(){
    $this->parent->listen('queryExecuted', 'requestSummaryData', $this, 'SummaryRowRequest');
  }

  function requestSummaryData(){
    if(!isset($this->attrs) || !is_array($this->attrs) || !count($this->attrs)){
      return false;
    }
    $this->attrs = \Verba\Configurable::substNumIdxAsStringValues($this->attrs, array('fx' => 'sum', 'format' => 'nodecimal'));

    $qm = $this->parent->QM();
    list($a) = $qm->createAlias();
    $f = array();
    foreach($this->attrs as $attr_code => $workCfg){
      if(isset($workCfg['fx']) && $workCfg['fx'] === false){
        continue;
      }
      switch($workCfg['fx']){
        case 'avg':
          $f[] = 'AVG(`'.$a.'`.`'.$attr_code.'`) as `'.$attr_code.'`';
          break;

        case 'sum':
        case null:
          $f[] = 'SUM(`'.$a.'`.`'.$attr_code.'`) as `'.$attr_code.'`';
          break;
      }
    }
    if(!count($f)){
      return;
    }
    $select_str = implode(', ', $f);

    $q = "SELECT ".$select_str."\nFROM ".implode(',', $qm->compiledFrom);
    if($qm->compiledJoin){
      $q .= "\n".$qm->compiledJoin;
    }
    if($qm->compiledCJoin){
      $q .= "\n".$qm->compiledCJoin;
    }
    if($qm->compiledWhere){
      $q .= "\nWHERE ".$qm->compiledWhere;
    }

    $sqlr = $this->DB()->query($q);
    if(!$sqlr || !$sqlr->getNumRows()){
      return;
    }
    $row = $sqlr->fetchRow();
    foreach($this->attrs as $attr_code => $workCfg){
      $value = isset($row[$attr_code]) ? $row[$attr_code] : null;
      if(isset($this->attrs[$attr_code]['handler']) && is_array($this->attrs[$attr_code]['handler'])){
        $modName = $this->attrs[$attr_code]['handler'][0];
        $method = $this->attrs[$attr_code]['handler'][1];
        if(\Verba\Hive::isModExists($modName)){
          $value = \Verba\_mod($modName)->$method($this, $row, $attr_code,$workCfg);
        }
      }
      if(isset($this->attrs[$attr_code]['format'])){
        switch($this->attrs[$attr_code]['format']){
          case 'money':
            $value = \Verba\reductionToCurrency($value);
            break;
          case 'nodecimal':
            $value = number_format($value, 0, '', ' ');
            break;
        }
      }
      $this->attrs[$attr_code]['value'] = $value;
    }
  }

}
