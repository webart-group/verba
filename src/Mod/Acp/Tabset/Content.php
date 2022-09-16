<?php

namespace Verba\Mod\Acp\Tabset;

class Content extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    return array(
      'MenuAef' => array(
        'pot' => false,
        'piid' => false,
        'instanceOf' => array('type' => 'node'),
      ),
      'ContentList' => array(
        'linkedTo' => array('type' => 'tab', 'id' => 'MenuAef'),
      ),
      'MetaAef' => array('linkedTo' => array('type' => 'tab', 'id' => 'MenuAef'))
    );
  }
}
?>