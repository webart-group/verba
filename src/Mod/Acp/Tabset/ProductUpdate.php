<?php

namespace Verba\Mod\Acp\Tabset;

class ProductUpdate extends \Verba\Mod\Acp\Tabset{
  public $maxLevel = 0;
  public $currentLevel = 0;

  function tabs(){

    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'updateform',
        'url' => '/acp/h/productadmin/cuform',
        'button' => array('title' => 'products acp tab title'),
      ),
      'MetaAef' => array(
        'linkedTo' => array('id' => 'ListObjectForm')
      ),
    );

    return $tabs;
  }
}
?>