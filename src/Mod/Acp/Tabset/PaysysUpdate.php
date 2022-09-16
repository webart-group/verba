<?php

namespace Verba\Mod\Acp\Tabset;

class PaysysUpdate extends \Verba\Mod\Acp\Tabset{

  function tabs(){

    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'updateform',
        'url' => '/acp/h/paysys/cuform',
        'button' => array('title' => 'paysys acp form update'),
      ),
      'LinkedTextblocks' => array(),
    );

    return $tabs;
  }

}
?>