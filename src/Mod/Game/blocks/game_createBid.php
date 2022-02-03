<?php

class game_createBid extends \Verba\Block\Html{
  /**
  * create AE
  *
  * @var \Act\AddEdit
  */
  public $ae;
  public $game;
  public $service;

  protected $gameId;
  protected $serviceId;
  //protected $currencyId;

  function prepare(){

    if($this->gameId === null && isset($_REQUEST['gameCatId'])){
      $this->gameId = (int)$_REQUEST['gameCatId'];
    }

    if($this->serviceId === null && isset($_REQUEST['serviceCatId'])){
      $this->serviceId = (int)$_REQUEST['serviceCatId'];
    }

    $mGame = \Verba\_mod('game');
    $this->game = $mGame->getGame($this->gameId);
    if(!is_object($this->game)){
      throw new \Exception\Routing('Bad game id');
    }

    $this->service = $this->game->getService($this->serviceId);
    if(!$this->service){
      throw new \Exception\Routing('Bad service id');
    }

//    if($this->currencyId === null && isset($_REQUEST['currencyId'])){
//      $this->currencyId = (int)$_REQUEST['currencyId'];
//    }
//    if(!$this->currencyId || !\Verba\_mod('currency')->getCurrency($this->currencyId, true)){
//      throw new \Exception\Routing('Bad currency Id');
//    }

  }

  function build(){
    $this->content = false;

    $U = User();
    /**
     * @var $mUser User
     */
    $mUser = \Verba\_mod('user');
    if(!$U->getAuthorized()){
      throw  new \Verba\Exception\Building('Guest are not wellcome');
    }

    $_store = \Verba\_oh('store');
    $store = $U->Stores()->getStore();
    if(!$store){
      throw  new \Verba\Exception\Building('Bad Store');
    }
    /**
    * @var \Model
    */
    $_product = isset($_REQUEST['NewObject'])
    && is_array($_REQUEST['NewObject'])
    && !empty($_REQUEST['NewObject'])
    && is_numeric($prod_ot_id = key($_REQUEST['NewObject']))
    && \Verba\isOt($prod_ot_id)
      ? \Verba\_oh($prod_ot_id)
      : false;

    if(!$_product){
      throw  new \Verba\Exception\Building('Bad product type or undefined');
    }

    $_serviceOh = isset($this->service->itemsOtId) ? \Verba\_oh($this->service->itemsOtId) : false;

    if(!$_serviceOh || $_serviceOh->getID() != $_product->getID()){
      throw  new \Verba\Exception\Building('Bad service or prod type mishmatch');
    }

    $_cat = \Verba\_oh('catalog');
    //$_cur = \Verba\_oh('currency');

    $this->ae = $_product->initAddEdit(array('action' => 'new'));
    $data = $_REQUEST['NewObject'][$_product->getID()];
    $data['storeId'] = $store->id;

    //$this->ae->addToLink($_store->getID(), $store->id);
    $this->ae->addToLink($_cat->getID(), $this->service->id);
    $this->ae->addToLink($_cat->getID(), $this->game->id, array('rule' => 'game'));

    $this->ae->setGettedData($data);
    $this->ae->addedit_object();

    $this->content = $this->ae->getIID();

    if(!$this->content){
      throw  new \Verba\Exception\Building($this->ae->log()->getMessagesAsStrHtml('error'));
    }

    if(!$store->first_offer){
      $store_ae = $_store->update($store->id, array('first_offer' => time()));
    }
    $profileGamebidsUrl = new \Url($mUser->getProfileUrl().'/offers');
    $this->addHeader('Location: '.$profileGamebidsUrl->get(true));
    setcookie(
      'gamebids-active-tab',
      'g'.$this->game->id.'-s'.$this->service->id,
      time()+(86400),
      $profileGamebidsUrl->get(false));
    return $this->content;
  }
}
?>