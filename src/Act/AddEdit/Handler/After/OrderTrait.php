<?php
namespace Verba\Act\AddEdit\Handler\After;

use Mod\Order;

trait OrderTrait{
  /**
   * @var Order
   */
  protected $mOrder;
  /**
   * @var \Mod\Order\Model\Order
   *
   */
  protected $Order;

  function prepare(){

    if(!$this->ah->getIID()){
      $this->log()->error('Bad order id');
      return false;
    }

    $this->mOrder = Order::getInstance();

    $this->Order = new \Mod\Order\Model\Order($this->ah->getIID());

    if(!$this->Order instanceof \Verba\Mod\Order\Model\Order){
      $this->log()->error('Order item not found');
      return false;
    }
    return true;
  }

}