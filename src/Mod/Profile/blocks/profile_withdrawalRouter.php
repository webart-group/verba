<?php
class profile_withdrawalRouter extends \Verba\Block{

  function route(){

    $blockCfg = array(
      'valid_otype' => 'withdrawal',
    );

    switch($this->rq->node){

      case 'create':
        $b = new account_withdrawalCreate($this, $blockCfg);
        break;

      case 'cuform':
        $b = new account_withdrawalForm($this, $blockCfg);
        break;
      case '':
        $blockCfg['userId'] = getUser()->getID();
        $b = new profile_withdrawalList($this, $blockCfg);
        break;
    }

    if(!isset($b)){
      throw new \Exception\Routing();
    }

    return $b->route();
  }

}
?>
