<?php

namespace Verba\Mod\Acp\Tabset;

class MusiccdCreate extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    return array(
      'ListObjectForm' => array(
        'action' => 'create',
        'ot' => 'musiccd',
        'url' => '/acp/h/musiccd/cuform',
        'button' => array('title' => 'musiccd acp tab title'),
      ),
    );
  }
}
?>