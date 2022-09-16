<?php
namespace Verba\Mod\Game\Cron;

class DisbleOverdueStartAt extends \Verba\Configurable {

  public $ot_id;

  function run(){

    $oh = \Verba\_oh($this->ot_id);
    if(!$oh->isA('startAt') || !$oh->isA('active')){
      return 0;
    }

    $q = "UPDATE ".$oh->vltURI()." SET `active` = '0' 
    WHERE `ot_id` = '".$oh->getID()."' 
    && `active` = '1' 
    && `startAt` > '0000-00-00 00:00:00'
    && `startAt` < '".\DBDriver\mysql\Driver::formatDateTime()."'";

    $sqlr = $this->DB()->query($q);
    $aff = $sqlr->getAffectedRows();
    if($aff)
    {
      $this->log()->event('Cron task '.__METHOD__.' for "'.$oh->getCode().'" deactivated entries: '.$aff);
    }

    return array(
      2,
      array(
        'startAt' => date('Y-m-d H:i:s', strtotime("+1 second"))
      )
    );
  }
}