<?php

namespace Verba\Mod\Acp\Tabset;

class ListAEForm extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    return array(
      'ListObjectForm' => array(
        'action' => 'someaction',
      )
    );
  }
}
?>