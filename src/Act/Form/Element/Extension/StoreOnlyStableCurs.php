<?php

namespace Verba\Act\Form\Element\Extension;

use \Verba\Act\Form\Element\Extension;

class StoreOnlyStableCurs extends Extension
{
  /**
   * @var AEF_ForeignSelect
   */
  public $fe;

  function engage(){
    $this->fe->listen('modifyQueryBefore', 'addWhereCondition', $this);
    return true;
  }

  function addWhereCondition(){
    $this->fe->qm->addWhere('0', 'unstable');
  }
}
