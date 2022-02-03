<?php
class profile_prequisitesActions extends \Verba\Block{

  function route(){

    $baseCfg = array('valid_otype' => 'prequisite');

    switch($this->rq->node){

      case 'create':
        //$this->rq->action = $this->rq->node;
        $baseCfg['responseAs'] =  'json-item-updated';
        $b = new \Mod\Routine\Block\CUNow($this, $baseCfg);
        break;

      case 'cuform':
        $b = new \Mod\Profile\Block\Prequisites\Form($this);
        break;

      case 'remove':
        $b = new \Mod\Routine\Block\Delete($this, $baseCfg);
        break;
    }

    if(!isset($b)){
      throw new \Exception\Routing();
    }

    return $b->route();
  }

}
?>
