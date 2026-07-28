<?php

namespace Verba\Mod\Acp\Tab\TabList;


class UsersList extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'user acp tabs list'
  );
  public $ot = 'user';
  public $action = 'list';
  public $url = '/acp/crud/user/list';

  function states(){
    $r = parent::states();
    $r['editlistobject'] = array(
      'type' => 'tabset',
      'name' => 'UserUpdate',
    );
    return $r;
  }
}
