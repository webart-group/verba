<?php

namespace Verba\Mod\Acp\Tabset;

class Paysys extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    $r = array(
      'PaysysAef' => array(
        'instanceOf' => array('type' => 'node'),
      ),
      'ShopCurrencies' => array(
        'linkedTo' => array('type' => 'tab', 'id'=>'PaysysAef'),
      ),
    );
    $psId = $this->node->getIid();
    $psMod = \Verba\_mod('payment')->getPaysysMod($psId);
    if($psMod instanceof \Mod && is_array($paysysTabs = $psMod->getPaysysAcpExtraTabs())){
      $r = array_replace_recursive($r, $paysysTabs);
    }

    return $r;
  }
}
?>