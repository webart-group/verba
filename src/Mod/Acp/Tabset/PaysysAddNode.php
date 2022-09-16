<?php

namespace Verba\Mod\Acp\Tabset;

class PaysysAddNode extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    return array(
      'PaysysAef' => array(
        'linkedTo' => array('type' => false)
      ),
    );
  }
}
?>