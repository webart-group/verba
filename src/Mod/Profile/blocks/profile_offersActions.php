<?php
class profile_offersActions extends \Verba\Block{

  function route(){

    $cfg = array(
      'owd' => new OffersWorkingData($this->rq)
    );

    switch($this->rq->node){

      case 'update':
        $b = new profile_offersUpdate($this, $cfg);
        break;

      case 'cuform':
        $b = new profile_offersUpdateForm($this, $cfg);
        break;

      case 'remove':
        $b = new profile_offersRemove($this);
        break;

      case '':
        $b = new profile_offersList($this, $cfg);
        break;
    }

    if(!isset($b)){
      throw new \Verba\Exception\Routing();
    }

    return $b->route();
  }

}
class OffersWorkingData{
  public $game;
  public $service;
  public $Store;
  public $listId;

  protected $valid;

  /**
   * OffersWorkingData constructor.
   * @param $rq Request
   * @throws ExceptionBuilding
   */
  function __construct($rq){

    $_cat = \Verba\_oh('catalog');
    $gameId = $rq->getParam('gameId', true);
    $serviceId = $rq->getParam('serviceId', true);
    $this->listId = $rq->getParam('slID', true);
    $cat_ot = $_cat->getID();

    if((!$gameId || !$serviceId)
      && is_string($this->listId) && !empty($this->listId)){
      list($pot, $serviceId) = $rq->getFirstParent();
      if($cat_ot != $pot){
        throw  new \Verba\Exception\Building('Invalid parent OT');
      }
      $br = \Verba\Branch::get_branch(array(
        $cat_ot => array(
          'aot' => array($cat_ot),
          'iids' => $serviceId,
        )
      ), 'up', 1);
      if(is_array($br['handled'][$cat_ot]) && count($br['handled'][$cat_ot]) == 2){
        $gameId = current($br['pare'][$cat_ot][$serviceId][$cat_ot]);
      }
    }elseif($rq->getParam('iid') && $rq->getParam('ot_id')){
      $_oh = \Verba\_oh($rq->getParam('ot_id'));
      $iid = $rq->getParam('iid');
      $prodOtId = $_oh->getID();
      $br = \Verba\Branch::get_branch(array(
        $prodOtId => array(
          'aot' => array($cat_ot),
          'iids' => $iid,
        )
      ), 'up', 2);
      if(is_array($br['handled'][$cat_ot])){
        $serviceId = current($br['pare'][$prodOtId][$iid][$cat_ot]);
        $gameId = current($br['pare'][$cat_ot][$serviceId][$cat_ot]);
      }
    }

    $this->game = \Verba\_mod('game')->getGame($gameId);
    if(!$this->game){
      throw  new \Verba\Exception\Building('Bad game id');
    }
    $this->service = $this->game->getService($serviceId);
    if(!$this->service){
      throw  new \Verba\Exception\Building('Bad service id');
    }

    if($rq->ot_id){

      $oh = \Verba\_oh($this->service->itemsOtId);
      if($oh->getID() != $rq->ot_id){
        throw  new \Verba\Exception\Building('Prod OT mismatch');
      }
    }

    $U = \Verba\User();
    $Store = $store = $U->Stores()->getStore();
    if(!$Store){
      throw  new \Verba\Exception\Building('Bad or unavaible store');
    }
    $this->Store = $Store;
  }

  function isValid(){

  }

  function validate(){

  }
}
?>
