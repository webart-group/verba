<?php

namespace Verba\Mod\Acp\Tabset;

class LedgerstaffUpdate extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'cuform',
        'ot' => 'ledgerstaff',
        'url' => '/acp/h/ledgeradmin/ledgerstaff/cuform',
        'button' => array('title' => 'ledger acp form ledgerstaff'),
      ),
    );
    return $tabs;
  }
}
?>