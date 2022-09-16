<?php

namespace Verba\Mod\Acp\Tabset;

class LedgercostUpdate extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'cuform',
        'ot' => 'ledgerentry',
        'url' => '/acp/h/ledgeradmin/cost/cuform',
        'button' => array('title' => 'ledger acp form cost'),
      ),
    );
    return $tabs;
  }
}
?>