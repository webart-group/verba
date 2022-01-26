<?php

namespace Verba\Act\Form\Element\Extension;

use \Verba\Act\Form\Element\Extension;


class OtSelector extends Extension
{
  public $role = '';

  function engage(){
    $this->fe->listen('loadValuesBefore', 'loadValues', $this);
  }

  function loadValues(){
    $otvalues = array();
    $_otype = \Verba\_oh('otype');
    $qm = new \Verba\QueryMaker($_otype, false, array('ot_code', 'title'));
    if($this->role) {
      $qm->addWhere($this->role, 'role');
    }
    $sqlr = $qm->run();
    $pac = $_otype->getPAC();
    if ($sqlr && $sqlr->getNumRows()) {
      while ($row = $sqlr->fetchRow()) {
        $otvalues[$row[$pac]] = $row['title'] . ' (' . $row['ot_code'] . ')';
      }
    }
    $this->fe()->setValues($otvalues);
  }
}
