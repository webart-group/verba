<?php
class profile_sellOrderStatusExtend extends profile_sell {

  public $templates = array(
    'content' => '/profile/orders/order-status-buttons.tpl',
  );

  function route(){
    return $this;
  }


  function build(){

    $btns = array();
    if($this->Order->status == 21){

      // Кнопка Подтвердить заказ (Торговец)
      $buttonConfirmedSeller = new profile_sellBtnConfirm($this->rq, array(
        'Order' => $this->Order,
      ));

      $buttonConfirmedSeller->run();
      $this->mergeHtmlIncludes($buttonConfirmedSeller);
      $btns['btnConfirmedSeller'] = $buttonConfirmedSeller->getContent();

      // Кнопка Возврат средств, отмена заказа.
      $buttonCashback = new profile_sellBtnCashback($this->rq, array(
        'Order' => $this->Order,
      ));

      $buttonCashback->run();
      $this->mergeHtmlIncludes($buttonCashback);
      $btns['btnCashback'] = $buttonCashback->getContent();

    }

    if(is_array($btns) && count($btns)){
      $this->tpl->assign('BUTTONS', implode('', $btns));
      $this->content = $this->tpl->parse(false, 'content');
    }else{
      $this->content = '';
    }

    return $this->content;
  }

}
?>