<?php

namespace Verba\Mod\Acp\Tabset;

class Blog extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    return array(
      'BlogList',
      'CatalogAef' => array(
        'action' => 'catalog',
        'button' => array(
          'title' => 'blog acp tab title'
        ),
        'url' => '/acp/h/blogadmin/catalog/cuform',
      ),
      'MetaAef' => array('linkedTo' => array('id' => 'CatalogAef'))
    );
  }
}
?>