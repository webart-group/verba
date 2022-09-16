<?php

namespace Verba\Mod\Acp\Tabset;

class BlockUpdate extends \Verba\Mod\Acp\Tabset{

  function tabs(){

    $tabs = array(
      'BlockSlots' => array(
        'action' => false,
        'ot' => 'block',
        'url' => '/acp/h/blockadmin/slots/list',
        'button' => array('title' => 'block acp tab blockslots'),
        'linkedTo' => array('type' => 'tab', 'id' => 'ListObjectForm'),
      ),
      'ListObjectForm' => array(
        'action' => 'updateform',
        'ot' => 'block',
        'button' => array('title' => 'block acp tab update'),
      ),
    );
    return $tabs;
  }
}
?>