<?php
class cart_PageBlock extends \Verba\Block\Html{
  public $role = 'cart-widget';
  function build(){
    $this->setScripts(array(
      array('cartView cartView_block orderDiscountView', 'shop'),
    ));
    $this->tpl->define(array('cartPlaceHolder' => 'shop/cart/placeholder.tpl'));
    $pad = \Verba\Lang::get('cart case');
    $root = \Verba\Lang::get('cart totalQuant');
    for($i=0; $i<10; ++$i){
      $padezh[$i] = call_user_func('make_padej_'.SYS_LOCALE, $i, $root, $pad);
    }
    $cfg = array(
      'container' => '#cart-holder',
      'minOrderCost' => \Verba\_mod('order')->gC('minimal_cost'),
      'case' => $padezh
    );
    $this->tpl->assign(array( 'CART_CFG' => json_encode($cfg) ));
    $this->content = $this->tpl->parse(false, 'cartPlaceHolder');
    return $this->content;
  }

}
?>
