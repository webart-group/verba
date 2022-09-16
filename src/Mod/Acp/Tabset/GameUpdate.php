<?php

namespace Verba\Mod\Acp\Tabset;

class GameUpdate extends \Verba\Mod\Acp\Tabset{

  function tabs(){

    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'updateform',
        'url' => '/acp/h/game/cuform',
        'button' => array('title' => 'game acp form update title'),
      ),
      'LinkedGameServers' => array(),
    );

    return $tabs;
  }

}
?>