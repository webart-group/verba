<?php

namespace Verba\Mod\Acp\Node;



class bannersgroup extends \Verba\Mod\Acp\Node{
  public $acpNodeType = 'bannersgroup';

  public $titleLangKey;

  function tabsets(){
    return array(
      'default' => array('class' => 'BannersGroup'),
    );
  }

  function menu(){
    $r = array();
    return $r;
  }
}
?>