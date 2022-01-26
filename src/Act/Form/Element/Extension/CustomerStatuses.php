<?php

namespace Verba\Act\Form\Element\Extension;

use \Verba\Act\Form\Element\Extension;

class CustomerStatuses extends Extension
{
  function engage(){
    $this->fe->listen('loadValuesBefore', 'loadCustomerStatuses', $this);
  }

  function loadCustomerStatuses(){
    $pd = array();
    //$mCust = \Verba\_mod('customer');
    $_cstatus = \Verba\_oh('customerstatus');
    $qm = new \Verba\QueryMaker($_cstatus, false, true);
    $qm->addWhere(1,'active');
    $qm->addOrder(array('priority' => 'd'));
    $sqlr = $qm->run();
    if(!$sqlr || !$sqlr->getNumRows()){
      return false;
    }
    $r = array();
    while($row = $sqlr->fetchRow()){
      $r[$row['id']] = $row[''];
    }
    $this->fe()->setValues($r);
  }
}
