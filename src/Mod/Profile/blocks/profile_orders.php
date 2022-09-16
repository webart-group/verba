<?php
class profile_orders extends \Verba\Block\Html {

  /**
   * @var \Verba\Mod\User\Model\User
   */
  public $U;
  protected $userId;
  protected $_orderSide = false;

  protected $baseCfg = array();

  function init()
  {
    if(!$this->U || !$this->U instanceof \Verba\Mod\User\Model\User
    || $this->U->getID() !=\Verba\User()->getID()){
      throw new Exception('Bad data');
    }

    $this->userId = $this->U->getID();

    $_order = \Verba\_oh('order');
    if($_order->getID() != $this->rq->ot_id){
      $this->rq->setOt($_order);
    }

  }

  function route(){

    if(!$this->_orderSide
    || !$this->userId){
      throw new \Verba\Exception\Routing('Bad params');
    }

    $this->baseCfg['U'] = $this->U;

    switch($this->rq->node){
      case '':
        $tabClassName = 'profile_'.$this->_orderSide.'sTab';
        $b = new $tabClassName($this, $this->baseCfg);
        break;

      case 'list':
        $rq = $this->rq->shift();
        $tabClassName = 'profile_'.$this->_orderSide.'sList';
        /**
         * @var $b \Verba\Block\Html
         */
        $b = new $tabClassName($rq, $this->baseCfg);
        $b->contentType = 'json';
        break;

    }

    if(!isset($b)){
      if($orderCode = \Verba\Mod\Order::i()->isOrderCode($this->rq->node))
      {
        $rq = $this->rq->shift();

        $rq->iid = $orderCode;
        $className = 'profile_'.$this->_orderSide;
        $b = new $className($rq, $this->baseCfg);
      }
    }
    /**
     * @var $b Block
     */
    if(!isset($b)){
      throw new \Verba\Exception\Routing();
    }

    return $b->route();
  }

}
?>