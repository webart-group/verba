<?php

namespace Verba\Mod\Acp\Tabset;

class Otypes extends \Verba\Mod\Acp\Tabset{

  function tabs(){
    return array('list' => array(
        'class' => 'List',
        'url' => '/acp/h/otype/list',
        'action' => 'list',
        'button' => array(
          'title' => 'otype tab index'
        ),
      ),
    );
  }
}
?>