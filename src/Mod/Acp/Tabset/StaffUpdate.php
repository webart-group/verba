<?php

namespace Verba\Mod\Acp\Tabset;

class StaffUpdate extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'cuform',
        'ot' => 'employee',
        'url' => '/acp/h/ledgeradmin/staff/cuform',
        'button' => array('title' => 'ledger acp form staff'),
      ),
    );
    return $tabs;
  }
}
?>