<?php
class profile_balopsRouter extends \Verba\Block{

  protected $userId;

  function route(){

    $_blp = \Verba\_oh('balop');

    $blockCfg = array(
      'contentType' => 'json',
      'userId' => getUser()->getID(),
    );

    $rq = $this->rq->shift();
    $rq->ot_id = $_blp->getID();

    switch($this->rq->node){
      case '':
      case 'list':
        $b = new profile_balopsList($rq, $blockCfg);
        break;
    }

    if(!isset($b)){
      throw new \Exception\Routing();
    }

    return $b->route();
  }

}
?>
