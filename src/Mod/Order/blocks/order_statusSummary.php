<?php
class order_statusSummary extends \Verba\Block\Html{

  public $templates = array(
    'content' => 'shop/order/status/summary.tpl',
    'summary-discounts' => 'shop/order/status/summary-discounts.tpl',
    'summary-discount-details-item' => 'shop/order/status/summary-discount-details-item.tpl',
    'summary-shipping' => 'shop/order/status/summary-shipping.tpl',
    'summary-total' => 'shop/order/status/summary-total.tpl',
  );

  public $tplvars = array(
    'ORDER_SUMMARY_DISCOUNTS' => '',
    'ORDER_DISCOUNT_ITEMS' => '',
    'ORDER_DISCOUNT_VALUE' => '',
    'ORDER_DISCOUNT_PERCENT' => '',
    'ORDER_DISCOUNT_DETAILS' => '',
    'ORDER_SUMMARY_SHIPPING' => '',
    'ORDER_SUMMARY_TOTAL' => '',
  );

  /**
   * @var \Mod\Order\Model\Order
   */
  public $Order;
  protected $curr;
  protected $paysys;
  public $grid_cfg;

  function init(){
    if(is_array($this->grid_cfg)){
      $this->tpl->assign($this->grid_cfg);
    }
  }

  function build(){

    $this->content = '';
    if(!$this->Order instanceof \Mod\Order\Model\Order){
      return $this->content;
    }
    $this->curr = $this->Order->getCurrency();

    $this->tpl->assign(array(
      'ORDER_CURRENCY_UNIT' => $this->curr->symbol,
      'ORDER_TOPAY_COST' => number_format($this->Order->getTopay(), 2, '.',' '),
      'ORDER_PAYSYS_TITLE' => $this->Order->getPaysys()->title,
    ));

    // total
    if($this->Order->getTopay() != $this->Order->getTotal()){
      $this->tpl->assign(array(
        'ORDER_TOTAL_COST' => number_format($this->Order->getTotal(), 2, '.',' '),
      ));
      $this->tpl->parse('ORDER_SUMMARY_TOTAL', 'summary-total');
    }

    // summary discount
    if($this->Order->discount > 0){
      $this->tpl->assign(array(
        'ORDER_DISCOUNT_PERCENT' => \Verba\reductionToCurrency($this->Order->getDiscountPercent()),
        'ORDER_DISCOUNT_VALUE' => number_format($this->Order->getDiscountValue(), 2,'.', ' '),
      ));

      $dd = $this->Order->getDiscountDetails();
      if(is_array($dd) && !empty($dd)){
        foreach($dd as $did => $ddetails){
          if($ddetails['affect'] == 'goods'){
            continue;
          }
          $this->tpl->assign(array(
            'ORDER_DD_ITEM_TITLE' => $ddetails['title'],
            'ORDER_DD_ITEM_VALUE' => number_format($ddetails['value'], 2,'.', ' '),
            'ORDER_DD_ITEM_PERCENT' => reductionToFloat($ddetails['percent']),
          ));
          $this->tpl->parse('ORDER_DISCOUNT_ITEMS', 'summary-discount-details-item', true);
        }
      }
      $this->tpl->parse('ORDER_SUMMARY_DISCOUNTS', 'summary-discounts');
    }

    // Delivery cost
    if($this->Order->getShipping() > 0){
      $this->tpl->assign(array(
        'ORDER_SHIPPING_COST' => number_format($this->Order->getShipping(), 2,'.', ' '),
        'ORDER_DELIVERY_CLASS_SIGN' => ''
      ));

      $this->tpl->parse('ORDER_SUMMARY_SHIPPING', 'summary-shipping');
    }

    $this->content = $this->tpl->parse(false, 'content');
    return $this->content;
  }

}

?>
