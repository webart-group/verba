<?php

namespace Verba\Mod\Acp\Tabset;


namespace Verba\Mod\Acp\Tabset;

class BannerUpdate extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'updateform',
        'ot' => 'banner',
        'url' => '/acp/h/banneradmin/cuform',
        'button' => array('title' => 'banner acp tab title'),
      ),
    );
    return $tabs;
  }
}
?>