<?php
class cron_shopRecalcCurrencyPaysysPairsRatio extends \Verba\Block {

  function build(){

    $mShop = \Verba\_mod('shop');
    $mShop->recalcCPPR();
    return array(
      2,
      array(
        'startAt' => date('Y-m-d H:i:s', strtotime("+3 hour"))
      )
    );
  }
}
