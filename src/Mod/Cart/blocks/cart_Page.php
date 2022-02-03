<?php
class cart_Page extends \Verba\Block\Html{

  function build(){
    $this->tpl->define(array(
      'cart-page' => 'shop/cart/cartpage.tpl',
    ));

    $cfg = array(
      'container' => '#cart-page-placeholder',
      'minOrderCost' => \Verba\_mod('order')->gC('minimal_cost'),
    );
    $this->tpl->assign(array(
      'CARTPAGE_CFG' => json_encode($cfg),
      'CURRENCY_SWITCHER_CFG' => '',
    ));

    return $this->tpl->parse(false, 'cart-page');
  }

}
// OrderPage ранее это по сути страница корзины
// и order_Page необходимо переделать в cart_Page
/*
class order_Page extends order_Muter{

  public $templates = array(
    'cart' => 'shop/order/create/wrap.tpl',
    'cart_empty' => 'shop/order/create/empty.tpl',
    'paysysSelector' => 'shop/order/paysysSelector/template.tpl',
  );

  function prepare(){
    $this->addScripts(
      array('form', 'form'),
      array('orderForm cartView cartView_order paysysSelector', 'shop')
    );
    $this->addCSS(
      array('form'),
      array('promotion order paysys-selector')
    );
    $this->getParent('HtmlBody')->tplvars['PAGE_SCHEME'] = 'order';
  }

  function build(){

    $backUrl = \Verba\Hive::getBackURL();
    if(!$backUrl){
      $backUrl = '/';
    }
    $this->tpl->assign(array(
      'CART_PAGE_BACK_URL' => $backUrl,
    ));
    $this->tpl->parse('PAYSYS_TEMPLATE', 'paysysSelector');

    $items = \Verba\_mod('Cart')->getItems();
    if(!is_array($items) || count($items) == 0){
      $this->content = $this->tpl->parse(false, 'cart_empty');
      return $this->content;
    }

    $_order = \Verba\_oh('order');
    $bp = array(
      'ot_id' => $_order->getID(),
      'action' => 'createform',
      'cfg' => 'public/order',
      'iid' => false
    );
    $form = $_order->initForm($bp);
    $cfg = array(
      'container' => '#cart-page',
      'orderForm' => array(
        'name' => $form->getFormName(),
      )
    );

    $pssCfg = array(
      'items' => \Verba\_mod('shop')->getPaymentSelectorItems(),
    );
    $this->tpl->assign(array(
      'CART_CFG' => json_encode($cfg),
      'PSYS_SELECTOR_CFG' => json_encode($pssCfg),
    ));
    $b = new textblock_noTitle(array('iid' => 'order_info_text'));
    $b->prepare();
    $b->build();
    $this->tpl->assign(array(
      'AE_FORM' => $form->makeForm(),
      'ORDER_TEXT_CONTENT' => $b->content,
    ));
    $this->content = $this->tpl->parse(false, 'cart');
    return $this->content;
  }
}
*/
?>
