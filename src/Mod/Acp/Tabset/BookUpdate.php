<?php

namespace Verba\Mod\Acp\Tabset;

class BookUpdate extends \Verba\Mod\Acp\Tabset{

  function tabs(){

    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'updateform',
        'url' => '/acp/h/book/cuform',
        'button' => array('title' => 'book acp form edit'),
      ),
      'LinkedComments' => array(),
      'MetaAef' => array('linkedTo' => array('id' => 'ListObjectForm'))
    );

    return $tabs;
  }
}
?>