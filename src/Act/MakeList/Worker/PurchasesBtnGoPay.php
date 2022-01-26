<?php
namespace Verba\Act\MakeList\Worker;

class PurchasesBtnGoPay extends PurchasesBtn{

  public $code = 'gotopay';
  public $urlBase = false;

  function init(){

    parent::init();

    $url = new \Verba\Url(\Mod\Order::getInstance()->gC('url processpayment'));
    $url->setParams(array('iid'=>''));
    $this->urlBase = $url->get(true);

  }

}