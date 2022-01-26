<?php

namespace Verba\Act\Form\Element;

use \Verba\Html\Element;

class OrderItems extends Element
{

  public $templates = array(
    //'wrap' => '/aef/fe/order-items/wrap.tpl',
//    'item' => '/aef/fe/order-items/item.tpl',
//    'extra' => '/aef/fe/order-items/extra.tpl',
//    'extraItem' => '/aef/fe/order-items/extraItem.tpl',
  );

  function makeE(){
    $this->fire('makeE');
    if($this->aef()->getAction() != 'edit'){
      $this->setE('');
    }else{
      $rq = new Request(array(
        'ot_id' => \Verba\_oh('order')->getID(),
        'iid' => $this->aef()->getIid()));

      $itemsAndSummary = new order_statusItems($rq, array(
        //'templates' => $this->templates,
        'searchByCode' => false,
        'printSummary' => false,
        'parsePromotions' => true,
      ));
      $itemsAndSummary->prepare();

      $this->setE($itemsAndSummary->build());
    }
    $this->fire('makeEFinalize');
  }

}