<?php
class order_Page extends \Verba\Block\Html {

  public $templates = array(
    'content' => 'shop/order/page/content.tpl',
  );

  public $items = array(
    'PANEL_ORDER_STATUS' => '',
    'PANEL_ORDER_STATUS_EXTEND' => '',
    'PANEL_STORE_INFO' => '',
    'PANEL_STORE_REVIEWS' => '',
    'PANEL_PROFILE_INFO' => '',
    'PANEL_ORDER_CHAT' => '',
  );

  public $tplvars = array(
    'ORDER_WRAP_CLASSES' => '',
  );

  /**
   * @var \Mod\Order\Model\Order
   */
  protected $Order;

  public $prodItem;

  public $prodItemId;
  /**
   * @var \Model
   */
  public $_prod;
  /**
   * @var \Model\Store
   */
  public $Store;

  protected $urlBase;

  protected $orderStatusClass = 'order_Status';

  public $classes = array(
    'order-page',
  );

  function init(){

    if(!is_object($this->Order) && $this->rq->iid){
      $this->Order = \Mod\Order::i()->getOrder($this->rq->iid);
    }

    if(!is_object($this->Order) || !$this->Order instanceof \Mod\Order\Model\Order || !$this->Order->getId()){
      throw new \Exception\Routing('Order not found');
    }

    $orderItems = $this->Order->getItems();
    if(!is_array($orderItems) || !count($orderItems)){
      throw  new \Verba\Exception\Building('Order items not found');
    }

    foreach ($orderItems as $item){
      $this->_prod = \Verba\_oh($item['ot_id']);
      $this->prodItem = $this->_prod->initItem($item);
      break;
    }

    if(!$this->prodItem){
      throw new Exception('Unknown Prod');
    }

    $this->Store = new \Model\Store($this->prodItem->getNatural('storeId'), 'store');
    if(!$this->Store->id){
      throw new Exception('Unknown Prod Store');
    }
  }

  function route(){

    $this->mergeHtmlIncludes(new page_htmlIncludesForm($this->rq));

    $this->addItems(array(

      'PANEL_ORDER_STATUS' => new page_coloredPanel($this, array(
        'extra_css_class' => 'panel-order-status',
        'scheme' => 'green',
        'title' => \Verba\Lang::get(
          'order public status panel_title',
          array(
            'order_code' => $this->Order->getCode(),
            'order_created' => utf8fix(strftime("%d %b %Y&nbsp;&nbsp;&nbsp;%H:%I", strtotime($this->Order->created)))
          )
        ),
        'items' => array(new $this->orderStatusClass($this, array(
          'Order' => $this->Order
        ))),
      )),
    ));

    return $this;
  }

  function prepare(){
    parent::prepare();
    if(is_array($this->classes) && count($this->classes)){
      $this->tpl->assign(array(
        'ORDER_WRAP_CLASSES' => ' ' . implode(' ', $this->classes),
      ));
    }
  }

  function setUrlBase($var){
    if(!is_string($var) || empty($var)){
      return false;
    }
    $this->urlBase = $var;
    return $this->urlBase;
  }

  function getUrlBase(){
    return $this->urlBase;
  }
}
