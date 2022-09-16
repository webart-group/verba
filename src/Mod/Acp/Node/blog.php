<?php

namespace Verba\Mod\Acp\Node;



class blog extends \Verba\Mod\Acp\Node{
  public $acpNodeType = 'blog';
  public $ot = 'catalog';
  public $titleLangKey = 'blog acp node name';

  function tabsets(){
    return array(
      'default' => 'Blog',
    );
  }

  function menu(){
    return array(
      'addnewnode' => array(
        'title' => \Verba\Lang::get('acp nodemenu addnew'),
        'type' =>'tabset',
        'cfg' => array(
          'tabs' => array(
            'CatalogAef' => array(
              'action' => 'catalog',
              'linkedTo' => array('type' => 'node'),
              'iid' => false,
              'instanceOf' => false,
              'url' => '/acp/h/blogadmin/catalog/cuform',
              'button' => array(
                'title' => 'blog acp tab title',
              )
            ),
          ),
        ),
      ),
      'deletenode' => array(
        'title' => \Verba\Lang::get('blog acp node menu delete'),
        'cfg' => array(
          'url_sfx' => '/blogadmin/catalog/remove',
          'action' => 'catalog',
        )
      )
    );
  }
}
?>