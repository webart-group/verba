<?php

namespace Verba\Mod\Acp\Tab\TabList;


class FinstatDay extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'ledger acp tab finstatday'
  );
  public $ot = 'finstatday';
  public $action = 'finstatday';
  public $url = '/acp/h/ledgeradmin/finstatday';

  function states(){
    $r = parent::states();
    $r['editlistobject'] = array(
      'type' => 'tabset',
      'name' => 'FinstatDayUpdate',
    );
    return $r;
  }
}
?>