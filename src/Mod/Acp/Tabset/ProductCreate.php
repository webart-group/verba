<?php

namespace Verba\Mod\Acp\Tabset;

class ProductCreate extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    return array(
      'ListObjectForm' => array(
        'action' => 'createform',
        'ot' => 'product',
        'url' => '/acp/h/productadmin/cuform',
        'button' => array('title' => 'products acp tab title'),
      ),
    );
  }
}
?>