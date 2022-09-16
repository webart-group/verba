<?php
class profile_purchaseOrder extends order_Page {

  /**
   * @var $U \Verba\Mod\User\Model\User
   */
  public $U;

  function init(){
    parent::init();

    if(!is_object($this->U) || !$this->U->active){
      throw new \Verba\Exception\Routing();
    }

  }

  function route(){

    $baseCfg = array(
      'Order' => $this->Order,
      'U' => $this->U,
    );

    /**
     * @var $mProfile Profile
     */
    $mProfile = \Verba\_mod('profile');
    $this->urlBase = $mProfile->getPurchaseActionUrl($this->Order);

    // proxy
    switch($this->rq->node){

      case 'confirm':
        $blockName = 'profile_purchaseConfirm';
        $routed = new $blockName($this->rq->shift(), $baseCfg);
        break;

      case 'reviews':
        $rq = $this->rq->shift();

        $routed = new store_reviewsAndForm($rq, $this->getReviewsCfg());
        break;
    }

    if(isset($routed)){
      return $routed->route();
    }

    if($this->rq->node != ''){
      throw new \Verba\Exception\Routing();
    }

    // добавление дефолтных блоков страницы заказа
    // вернет $this
    parent::route();

    $this->classes['order-sign'] = 'order-purchase';
    $mStore = \Verba\Mod\Store::getInstance();
    $this->addItems(array(

      'PANEL_STORE_REVIEWS' => new page_coloredPanel($this, array(
        'items' => array(new store_reviewsAndForm($this, $this->getReviewsCfg())),
        'title' => \Verba\Lang::get('store reviews panelTitle'),
        'scheme' => 'brown',
      )),
      'PANEL_ORDER_CHAT' => new page_coloredPanel($this, array(
          'items' => array('CONTENT' => new chatik_pageInstance($this, array(
            'channel' => $mStore->genChatChannelToUser($this->Store, $this->U),
            'notifierCfg' => 'user'
          ))),
          'title' => \Verba\Lang::get('profile orders chat panelTitle'),
          'scheme' => 'blue',
        )
      ),
      'PANEL_STORE_INFO' => new store_infoAndAnnounces($this, array('Store' => $this->Store)),
    ));

    if($this->Order->status == 21){
      $this->addItems(array(
        'PANEL_ORDER_STATUS_EXTEND' => new profile_purchaseOrderStatusExtend($this, $baseCfg)
      ));
    }
    return $this;
  }

  function getReviewsCfg(){

    return array(
      'urlBase' => (new \Url($this->urlBase))->shiftPath('reviews')->get(),
      'Order' => $this->Order,
      'Store' => $this->Store,
      'prodItem' => $this->prodItem,
      'addProductAsParent' => true,
      'listId' => 'or_'._oh('order')->getID().'_'.$this->Order->getId(),
    );
  }
}
?>