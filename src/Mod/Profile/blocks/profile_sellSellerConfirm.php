<?php
class profile_sellSellerConfirm extends profile_sell {

  public $contentType = 'json';

  use profile_orderResponse;

  function init(){

    $this->profileSuccessMsgLKey = 'orderConfirmedSuccessfull';

    parent::init();

    if(!$this->Order->canBeConfirmedBySeller()){
      throw new \Exception\Routing('Unable to be confirmed by Seller');
    }

  }

  function route(){
    return $this;
  }

  function build(){
    $_order = \Verba\_oh('order');
    $ae = $_order->initAddEdit('edit');
    $ae->setIid($this->Order->getId());
    $ae->setGettedData(array(
      'confirmedSeller' => 1,
    ));
    $ae->addExtendedData(array(
      'ProfileU' => $this->U,
      '__seller_confirm_script_key' => SYS_SCRIPT_KEY
    ));

    $ae->addedit_object();
    if($ae->haveErrors()){
      throw  new \Verba\Exception\Building($ae->log()->getMessagesAsString('error'));
    }

    $this->content = $this->wrapResponse($ae);
    return $this->content;

  }
}
?>