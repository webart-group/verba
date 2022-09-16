<?php

namespace Verba\Mod\Acp\Tabset;

class OrderUpdate extends \Verba\Mod\Acp\Tabset{
  function tabs(){

    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'updateform',
        'ot' => 'order',
        'url' => '/acp/h/order/cuform',
        'button' => array('title' => 'order acp updateform title'),
      ),
      //'OrderProducts' => array('linkedTo' => array('type'=> 'tab', 'id' => 'ListObjectForm')),
      'OrderTransactions' =>  array('linkedTo' => array('type'=> 'tab', 'id' => 'ListObjectForm')),
    );

    return $tabs;
  }
}
?>