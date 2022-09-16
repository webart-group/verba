<?php

namespace Verba\Mod\Acp\Tab\TabList;


class LedgerStaff extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'ledger acp tab ledgerstaff'
  );
  public $ot = 'ledgerstaff';
  public $action = 'ledgerstafflist';
  public $url = '/acp/h/ledgeradmin/ledgerstaff';

  function states(){
    $r = parent::states();
    $r['editlistobject'] = array(
      'type' => 'tabset',
      'name' => 'LedgerstaffUpdate',
    );
    return $r;
  }
}
?>