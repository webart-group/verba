<?php
class profile_sells extends profile_orders {

  protected $_orderSide = 'sell';

  public $Store;


  function init(){
    parent::init();
    if($this->U){
      $this->Store = $this->U->Stores()->getStore();
    }
    if(!$this->Store || !$this->Store instanceof \Model\Store){
      throw new \Exception\Routing('Unknown param');
    }
  }

}
?>
