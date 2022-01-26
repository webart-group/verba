<?php

namespace Verba\Act\Form\Element;

use \Verba\Html\Element;

class OrderDiscountCombined extends Element
{
  public $templates = array(
    'wrap' => '/shop/order/acp/form/order-discount-combined.tpl',
    'item' => '/shop/order/acp/form/order-discount-combined-item.tpl',
  );

  function makeE(){
    $this->fire('makeE');

    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);
    $discount = 0;
    if($this->aef()->getAction() == 'edit'
      && is_string($dd = $this->aef->getExistsValue('discountDetails'))
      && !empty($dd)){
      $dd = json_decode($dd);
      foreach($dd as $did => $ddetails){
        $this->tpl->assign(array(
          'ORDER_DD_ITEM_TITLE' => $ddetails->title,
          'ORDER_DD_ITEM_VALUE' => number_format($ddetails->value, 2,'.', ' '),
        ));
        $this->tpl->parse('ORDER_COMBINED_ITEMS', 'item', true);

        $discount += (float)$ddetails->value;
      }
    }else{
      $this->tpl->assign(array(
        'ORDER_COMBINED_ITEMS' => '',
      ));
    }

    $this->tpl->assign(array(
      'E_TITLE' => $this->displayName,
      'ORDER_DISCOUNT_VALUE' => number_format($discount, 2,'.', ' '),
      'ORDER_DISCOUNT_COMBINED_VIS_SIGN' => $discount ? '' : ' no-discount'
    ));

    $this->setE($this->tpl->parse(false, 'wrap'));

    $this->fire('makeEFinalize');
  }
}
