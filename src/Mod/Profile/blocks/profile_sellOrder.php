<?php
class profile_sellOrder extends order_Page {

  public $css = array(
    array('sells', 'profile')
  );

  /**
   * @var $U \Verba\User\Model\User
   */
  public $U;

  protected $orderStatusClass = 'order_StatusSeller';

  function init(){

    parent::init();

    if(!is_object($this->U) || !$this->U->active){
      throw new \Exception\Routing();
    }

    if($this->Order->getStore()->owner != $this->U->getID()){
      throw new \Exception\Routing();
    }

  }

  function route(){

    $baseCfg = array(
      'Order' => $this->Order,
      'U' => $this->U,
    );

    // proxy

    switch($this->rq->node){
      case 'cashback':
        $blockName = 'profile_sellCancelCashback';
        $b = new $blockName($this->rq->shift(), $baseCfg);
        break;
      case 'confirm':
        $blockName = 'profile_sellSellerConfirm';
        $b = new $blockName($this->rq->shift(), $baseCfg);
        break;
    }

    /**
     * @var $b \Block\Html
     */
    if(isset($b)){
      return $b->route();
    }

    if($this->rq->node != ''){
      throw new \Exception\Routing();
    }
    /**
     * @var $mProfile Profile
     * @var $mChat Chatik
     */
    $mProfile = \Verba\_mod('profile');
    $this->urlBase = $mProfile->getSellActionUrl($this->Order);

    $this->classes['order-sign'] = 'order-sell';
    // добавление дефолтных блоков страницы заказа
    // вернет $this
    parent::route();

    $mStore = \Mod\Store::getInstance();
    $mChat = \Verba\_mod('Chatik');

    $this->addItems(array(
      'PANEL_ORDER_CHAT' => new page_coloredPanel($this, array(
          'items' => array('CONTENT' => new chatik_pageInstance($this, array(
            'channel' => $mStore->genChatChannelToUser($this->Store, $this->Order->owner),
            'notifierCfg' => $mChat->genNotifierCfgForStore($this->Store->getId()),
          ))),
          'title' => \Verba\Lang::get('profile orders chat panelTitleWithBuyer'),
          'scheme' => 'blue',
        )
      ),
      'PANEL_PROFILE_INFO' => new profile_publicViewStats($this, array('U' => new \Verba\User\Model\User($this->Order->owner))),
      'PANEL_ORDER_STATUS_EXTEND' => new profile_sellOrderStatusExtend($this, $baseCfg)
    ));
    return $this;
  }

  function getReviewsCfg(){

    return array(
      'urlBase' => (new \Url($this->urlBase))->shiftPath('reviews')->get(),
      'Order' => $this->Order,
      'Store' => $this->Store,
      'prodItem' => $this->prodItem,
      'listId' => 'or_'._oh('order')->getID().'_'.$this->Order->getCode(),
    );
  }
}
?>