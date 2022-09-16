<?php

namespace Verba\Mod\Acp\Tab\TabList;


class StaffList extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'ledger acp tab staffList'
  );
  public $action = 'stafflist';
  public $url = '/acp/h/ledgeradmin/staff/list';

  function states(){
    $r = parent::states();
    $r['editlistobject'] = array(
      'type' => 'tabset',
      'name' => 'StaffUpdate',
    );
    return $r;
  }
}
?>