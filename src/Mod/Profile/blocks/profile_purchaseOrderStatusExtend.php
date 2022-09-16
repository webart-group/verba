<?php
class profile_purchaseOrderStatusExtend extends profile_purchase {

  public $templates = array(
    'content' => '/profile/orders/order-status-buttons.tpl',
  );

  function route(){
    return $this;
  }


  function build(){

    $btns = array();

    $btnConfirm = new profile_purchaseBtnConfirm($this->rq, array(
      'Order' => $this->Order
    ));
    $btns['btnBlock'] = $btnConfirm->run();
    $this->mergeHtmlIncludes($btnConfirm);


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