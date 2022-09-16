<?php

namespace Verba\Mod\Acp\Tabset;

class VideocdUpdate extends \Verba\Mod\Acp\Tabset{

  function tabs(){

    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'updateform',
        'url' => '/acp/h/videocd/cuform',
        'button' => array('title' => 'videocd acp form edit'),
      ),
      'LinkedComments' => array(),
      'MetaAef' => array('linkedTo' => array('id' => 'ListObjectForm'))
    );

    return $tabs;
  }
}
?>