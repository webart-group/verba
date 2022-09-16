<?php

namespace Verba\Mod\Acp\Tabset;

class NodeCreateGame extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    return array(
      'GameAef' => array(
        'action' => 'createform',
        'linkedTo' => false
      ),
    );
  }
}
?>