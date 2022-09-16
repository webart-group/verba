<?php
class paysys_statusQiwi extends paysys_status{

  public $css = array(
    array('order-status-qiwi')
  );

  function prepare(){
    $this->tpl->define(array(
      'payment-button-extend' => 'shop/paysys/qiwi/client-number.tpl',
    ));
  }
}