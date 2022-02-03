<?php
class order_Create extends \Verba\Block\Html{

  public $templates = array(
    'orderResultBody' => 'shop/order/resultBody.tpl',
  );

  /**
   * @var \Mod\Order\CreateData
   */
  public $orderCreateData;

  function build() {

    $mOrder = \Mod\Order::i();

    try{

      if(!is_object($this->orderCreateData)){
        $this->orderCreateData = new \Mod\Order\CreateData(array());
      }

      if(!$this->orderCreateData->validate()){
        throw  new \Verba\Exception\Building($this->orderCreateData->log()->getMessagesAsStr('error'));
      }

      $ae = $mOrder->createOrder($this->orderCreateData);

      $order = $mOrder->getOrder($ae->getIID());

    }catch(Exception $e){
      $this->log()->error($e->getMessage());
      $this->tpl->define(array(
        'orderResultBody' => 'shop/order/resultBodyFailure.tpl',
      ));
      $this->tpl->assign(array(
        'ORDER_RESULT_TITLE' => \Verba\Lang::get('order public createform orderResultFailureTitle'),
        'ORDER_RESULT_MESSAGE' => \Verba\Lang::get('order public createform orderResultFailure'),
      ));
      $this->content = $this->tpl->parse(false, 'orderResultBody');
      return $this->content;
    }

    $url = \Mod\Profile::getInstance()->getPurchaseActionUrl($order);

    $this->tpl->assign(array(
      'ORDER_PROCESSING_LINK_URL' => $url,
      'ORDER_RESULT_TITLE' => \Verba\Lang::get('order public createform orderResultSuccessTitle'),
      'ORDER_RESULT_MESSAGE' => \Verba\Lang::get('order public createform orderResultSuccess'),
      'ORDER_CODE' => $order->getId(),
    ));

    $this->addHeader('Location', $url);
    $this->content = $this->tpl->parse(false, 'orderResultBody');
    return $this->content;
  }
}

?>
