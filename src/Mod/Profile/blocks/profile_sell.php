<?php
class profile_sell extends profile_order{

  protected $_orderSide = 'sell';
  /**
   * @var \Model\Store
   */
  public $Store;

  function init(){
    parent::init();

    if($this->U){
      $this->Store = $this->U->Stores()->getStore();
    }
    if(!$this->Store || !$this->Store instanceof \Model\Store){
      throw new \Verba\Exception\Routing('Unknown param');
    }
    if($this->Order->storeId != $this->Store->getId()){
      throw new \Verba\Exception\Routing('Bad access param');
    }

  }

  function route(){

    /**
     * @var $b Block
     */
    $b = parent::route();

    if($b){
      goto handle_routed;
    }

    if(!isset($b) || !is_object($b) || !$b instanceof Block){
      throw new \Verba\Exception\Routing();
    }


    handle_routed:

    return $b->route();

  }

}
?>