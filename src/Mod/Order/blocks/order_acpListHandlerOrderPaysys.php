<?php
class order_acpListHandlerOrderPaysys extends ListHandlerField {

  function run(){
    $mCurrency = \Verba\_mod('Currency');
    $c = $mCurrency->getCurrency($this->list->row['currencyId']);

    return
    $c->short.',&nbsp;'
    . $c->paysysId_value;
  }

}
?>