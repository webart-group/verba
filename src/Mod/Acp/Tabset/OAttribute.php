<?php

namespace Verba\Mod\Acp\Tabset;

class OAttribute extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    return array(
      'List' => array(
        'class' => 'List',
        'url' => '/acp/h/oattribute/list',
        'action' => 'list',
        'button' => array(
          'title' => 'oattribute tab index'
        ),
      ),
    );
  }
}
?>