<?php

namespace Verba\Mod\Acp\Tabset;

class UserUpdate extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'updateform',
        'ot' => 'user',
        'url' => '/acp/h/user/cuform',
        'button' => array('title' => 'user acp form update title'),
      ),
    );
    return $tabs;
  }
}
