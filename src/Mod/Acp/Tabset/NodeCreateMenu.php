<?php

namespace Verba\Mod\Acp\Tabset;

class NodeCreateMenu extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    return array(
      'MenuAef' => array(
        'linkedTo' => array('type' => 'node')
      ),
    );
  }
}
?>
