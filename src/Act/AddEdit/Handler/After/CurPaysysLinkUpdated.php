<?php
namespace Verba\Act\AddEdit\Handler\After;

use \Verba\Act\AddEdit\Handler\After;

class CurPaysysLinkUpdated extends After{

  function run(){
    $_ps = \Verba\_oh('paysys');
    $psOtId = $_ps->getID();

    $linked = $this->ah->getLinked();
    $unlinked = $this->ah->getUnlinked();

    $updRequired = false;
    // связь валюта-платежка добавлена

    // на ввод
    if(isset($linked['c'][$psOtId]['input'])
      && !empty($linked['c'][$psOtId]['input'])){
        $updRequired = true;
      }
      // на вывод
    if(isset($linked['c'][$psOtId]['output'])
      && !empty($linked['c'][$psOtId]['output'])) {
      $updRequired = true;
    }

    // связь валюта-платежка удалена

    // на ввод
    if(isset($unlinked['c'][$psOtId]['input'])
      && !empty($unlinked['c'][$psOtId]['input'])){
      $updRequired = true;
    }
    // на вывод
    if(isset($unlinked['c'][$psOtId]['output'])
      && !empty($unlinked['c'][$psOtId]['output'])){

      $updRequired = true;

      //выставить активность - 0 всем кошелькам магазинов
      // с отключенной валютной парой
      $oCurId = $this->ah->getIID();
      $psIids = $unlinked['c'][$psOtId]['output'];
      $_acc = \Verba\_oh('account');

      $q = "UPDATE ".$_acc->vltURI()." SET 
      active = '0' 
      WHERE currencyId = '".$oCurId."'
       && paysysId IN ('".implode("','", $psIids)."')";

      $sqlr = $this->DB()->query($q);

      $this->log()->event('Currency-Paysys unlinked. oCurId['.var_export($oCurId, true)
        .'], PaysysIds['.implode(',', $psIids)
        .']. Affected accounts: '.$sqlr->getAffectedRows());

    }

    if($updRequired == true){
      $Shop = \Verba\_mod('shop');
      $Shop->refreshCpprSystem();
    }

    return true;
  }
}
