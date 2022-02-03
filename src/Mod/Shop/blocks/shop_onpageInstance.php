<?php
class shop_onpageInstance extends \Verba\Block\Html{

  function build(){

    $this->setScripts(array(
      array('shop', 'shop'),
    ));

    $mShop = \Mod\Shop::getInstance();
    $cfg = $mShop->packToCfg();
    $this->addJsBefore("
window.ShopInstance = new Shop(".json_encode($cfg,JSON_FORCE_OBJECT).");
window.ShopInstance.init();"
    );
    return true;
  }

}
?>
