<?php

namespace Verba\Mod\Acp\Node;



class menu extends \Verba\Mod\Acp\Node{
  public $acpNodeType = 'menu';

  function tabsets(){
    return array(
      'default' => array('class' => 'Menu'),
    );
  }

  function menu(){
    return array(
      'addnewnode' => array(
        'title' => \Verba\Lang::get('acp nodemenu addnew'),
        'type' =>'tabset',
        'name' => 'NodeCreateMenu',
        'cfg' => array(
          'tabs' => array(
            'MenuAef' => array(
              'iid' => false,
              'instanceOf' => array('type' => 'node'),
              'inheritUrl' => 1,
              'inheritUrlThis' => true,
            )
          )
        )
      ),
      'deletenode' => array(
        'title' => \Verba\Lang::get('acp nodemenu delete'),
        'cfg' => array(
          'url_sfx' => '/menu/remove'
        )
      ),
    );
  }
}
?>