<?php
namespace Verba\Act\MakeList\Worker;

use \Verba\Act\MakeList\Worker;

class TempProducts extends Worker{

  public $onlytemp = false;

  function init(){
    $this->parent->listen('beforeQuery', 'beforeQuery', $this, 'addTempProductsFilter');
  }

  function beforeQuery(){
    $qm = $this->parent->QM();
    list($a) = $qm->createAlias();
    $fSqlAlias = 'ipk_temp_prods';
    $qm->removeWhere($fSqlAlias);
    if($this->onlytemp == true){
      $qm->addWhere(1, $fSqlAlias, 'tmp');
    }else{
      $qm->addWhere(0, $fSqlAlias, 'tmp');
    }
  }
}
