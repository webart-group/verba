<?php

namespace Verba\Mod\Acp\Tabset;

class NodeCreateCatalog extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    return array(
      'CatalogAef' => array(
        'action' => 'createform',
        'linkedTo' => array('type' => 'node'),
        'iid' => false,
        'instanceOf' => false,
      ),
    );
  }
}
?>