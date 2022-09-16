<?php
class order_StatusSeller extends order_Status{

  public $parseSummary = false;

  function fillPssCfg()
  {
    $r = parent::fillPssCfg();
    $r['parsePaysysTitleRow'] = false;
    return $r;
  }
}
