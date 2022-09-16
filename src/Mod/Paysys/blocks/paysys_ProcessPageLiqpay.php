<?php
class paysys_ProcessPageLiqpay extends paysys_ProcessPage{

  function init(){
    $this->templates = array_merge($this->templates,
    array(
      'rqFromFields' => 'shop/paysys/liqpay/rq_form_fields.tpl',
    ));
  }

  function genContent($orderId){
    $prq = new PaySend_Liqpay($orderId);
    $order = \Verba\_mod('order')->getOrderByCode($orderId);
    $topay = $order->getTopay();
    $currShort = $order->currency['short'];

    $this->tpl->assign(array(
      'RQ_METHOD' => 'POST',
      'OPERATION_XML' =>  $prq->requestData,
      'OPERATION_SIGN' => $prq->signature,
      'RQ_URL' => $prq->url,
      'RQ_SUBMIT_BUTTON' => \Verba\Lang::get('liqpay paySubmitButton', array('topay' => $topay, 'unit' => $currShort)),
    ));
    $this->tpl->parse('RQ_FIELDS', 'rqFromFields');
    $prq->logRq();

    return $this->tpl->parse(false, 'rqFrom');
  }

}

?>
