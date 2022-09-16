<?php

namespace Verba\Mod\Acp\Tab\TabList;


class LedgerCost extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'ledger acp tab costList'
  );
  public $ot = 'ledgerentry';
  public $action = 'listcost';
  public $url = '/acp/h/ledgeradmin/cost';

  function states(){
    $r = parent::states();
    $r['editlistobject'] = array(
      'type' => 'tabset',
      'name' => 'LedgercostUpdate',
    );
    return $r;
  }
}
?>