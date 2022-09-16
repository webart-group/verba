<?php

namespace Verba\Mod\Acp\Tabset;

class Shop extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    return array(
      'OrderProps' => array(
        'class' => 'CustomizeModConfig',
        'url' => '/acp/h/cfgmodify/form/order',
        'button' => array(
          'title' => 'order acp tab props'
        )
      ),
      'ShopBaseCurrenyForm' => array(
        'url' => '/acp/h/currency/baseform',
        'action' => 'baseform',
        'button' => array(
          'title' => 'currency acp tab baseCurrency'
        )
      ),
      'UsertrustsList'
    );
  }
}
?>