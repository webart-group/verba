<?php

namespace Verba\Mod\Acp\Tab\TabList;


class PaysysList extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'paysys acp list title'
  );
  public $ot = 'paysys';
  public $action = 'list';
  public $url = '/acp/h/paysys/list';

  function states(){
    $r = array(
      'editlistobject' => array(
        'type' => 'tabset',
        'name' => 'PaysysUpdate',
      )
    );
    return $r;
  }

}
?>