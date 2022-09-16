<?php

namespace Verba\Mod\Acp\Tabset;

class LedgerincomeUpdate extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'cuform',
        'ot' => 'ledgerentry',
        'url' => '/acp/h/ledgeradmin/income/cuform',
        'button' => array('title' => 'ledger acp form income'),
      ),
    );
    return $tabs;
  }
}
?>