<?php
class profile_order extends profile_orders{

  public $scripts = array(
    array('profile_order_buttons', 'profile/tools'),
  );

  /**
   * @var \Mod\Order\Model\Order
   */
  public $Order;

  function init(){

    parent::init();

    $this->Order =  \Mod\Order::i()->getOrder($this->rq->iid);
    if(!$this->Order){
      throw new \Exception\Routing('Bad params');
    }

    $this->rq->iid = $this->Order->getId();
    $this->rq->setOt('order');
  }

  function route(){

    $this->baseCfg['U'] = $this->U;
    $this->baseCfg['Order'] = $this->Order;

    $blockName = 'profile_'.$this->_orderSide.'Order';

    if(!class_exists($blockName)){
      throw new \Exception\Routing();
    }

    /**
     * @var $b Block
     */
    $b = new $blockName($this->rq, $this->baseCfg);

    return $b->route();
  }

}
?>