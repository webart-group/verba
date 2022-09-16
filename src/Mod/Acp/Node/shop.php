<?php

namespace Verba\Mod\Acp\Node;


class shop extends \Verba\Mod\Acp\Node{
  public $acpNodeType = 'shop';
  public $titleLangKey = 'shop acp node name';

  function tabsets(){
    return array(
      'default' => 'Shop',
    );
  }
}

?>
