<?php

namespace Verba\Mod\Acp\Tab\TabList;


class OrdersList extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'order acp tab list'
  );
  public $action = 'list';
  public $url = '/acp/h/order/list';

  function states(){
    $r = array(
      'addlistobject' => false,
      'editlistobject' => array(
        'type' => 'tabset',
        'name' => 'OrderUpdate',
      )
    );
    return $r;
  }
}
?>