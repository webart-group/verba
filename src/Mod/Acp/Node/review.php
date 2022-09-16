<?php

namespace Verba\Mod\Acp\Node;



class review extends \Verba\Mod\Acp\Node{
  public $acpNodeType = 'review';

  public $titleLangKey = 'review acp node name';

  function tabsets(){
    return array(
      'default' => array('class' => 'Review'),
    );
  }

  function menu(){
    return array();
  }
}
?>