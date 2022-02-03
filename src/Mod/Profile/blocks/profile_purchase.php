<?php
class profile_purchase extends profile_order{

  protected $_orderSide = 'purchase';

  function init(){
    parent::init();

    if($this->Order->owner != $this->U->getId()){
      throw new \Exception\Routing('Bad access param');
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
      throw new \Exception\Routing();
    }


    handle_routed:

    return $b->route();
  }

}
?>