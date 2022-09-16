<?php

namespace Verba\Mod\Acp\Node;


class customers extends \Verba\Mod\Acp\Node{
  public $acpNodeType = 'customers';
  public $titleLangKey = 'customer acp node name';

  function tabsets(){
    return array(
      'default' => array('class' => 'Customers'),
    );
  }

  function menu(){
    return array();
  }
}
?>