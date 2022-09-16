<?php

namespace Verba\Mod\Acp\Tab\TabList;


class LedgerIncome extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'ledger acp tab incomeList'
  );
  public $ot = 'ledgerentry';
  public $action = 'listincome';
  public $url = '/acp/h/ledgeradmin/income';

  function states(){
    $r = parent::states();
    $r['editlistobject'] = array(
      'type' => 'tabset',
      'name' => 'LedgerincomeUpdate',
    );
    return $r;
  }
}
?>