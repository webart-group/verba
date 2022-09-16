<?php

namespace Verba\Mod\Acp\Tabset;

class BlogUpdate extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'updateform',
        'ot' => 'blog',
        'url' => '/acp/h/blogadmin/cuform',
        'button' => array('title' => 'blog acp tab title'),
      ),
      'MetaAef' => array('linkedTo' => array('type' => 'tab', 'id' => 'ListObjectForm'))
    );
    return $tabs;
  }
}
?>