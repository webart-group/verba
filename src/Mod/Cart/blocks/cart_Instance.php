<?php
class cart_Instance extends \Verba\Block\Html{

  function build(){

    $this->setScripts(array(
      array('cart customer', 'shop'),
    ));
    /**
     * @var \Verba\Mod\Cart $Cart
     */
    $Cart = \Verba\_mod('cart');
    $paysys = $Cart->getPaysys();
    $currency = $Cart->getCurrency();

    $cfg = $Cart->packToCfg();
    $this->addJsBefore("
window.CartInstance = new Cart(".json_encode($cfg).");
window.CartInstance.init();");
    return true;
  }

}
?>
