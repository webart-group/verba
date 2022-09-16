<?php

namespace Verba\Mod\Acp\Tabset;

class CollectionUpdate extends \Verba\Mod\Acp\Tabset{

  function tabs(){

    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'updateform',
        'url' => '/acp/h/collection/cuform',
        'button' => array('title' => 'collection acp form edit'),
      ),
      'LinkedComments' => array(),
      'MetaAef' => array('linkedTo' => array('id' => 'ListObjectForm'))
    );

    return $tabs;
  }
}
?>