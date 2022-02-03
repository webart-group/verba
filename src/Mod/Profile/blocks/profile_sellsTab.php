<?php
class profile_sellsTab extends profile_ordersTab{

  protected $_orderSide = 'sell';
  public $Store;
  public $titleLangKey = false;

  function init()
  {
    $this->baseCfg['Store'] = $this->Store;
    parent::init();
  }

}
?>
