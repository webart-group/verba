<?php
class order_Transactions extends \Verba\Block\Html{

  public $templates = array(
    'table' => 'shop/order/acp/transactions/table.tpl',
    'row' => 'shop/order/acp/transactions/row.tpl',
    'emptyrow' => 'shop/order/acp/transactions/emptyrow.tpl',
    'details_cell' => 'shop/order/acp/transactions/details_cell.tpl',
  );

  function build(){
    $bp = $this->request->asArray();
    try{
      $_order = \Verba\_oh('order');
      $orderid = false;
      $order = false;
      if(is_array($bp['pot'][$_order->getID()]) && count($bp['pot'][$_order->getID()])){
        reset($bp['pot'][$_order->getID()]);
        $orderid = current($bp['pot'][$_order->getID()]);
        $order = \Verba\_mod('order')->getOrder($orderid);
      }

      if(!$order instanceof \Mod\Order\Model\Order){
        throw new Exception('Unknown order');
      }
      $trans = $order->getTrans();

      if(!count($trans)){
        $this->tpl->parse('ORDER_TRANS_ROWS', 'emptyrow');
      }else{
        foreach($trans as $id => $item){
          $this->tpl->assign(array(
            'ORDER_TRAN_ID' => $id,
            'ORDER_TRAN_DETAILS' => $item->getTranDataAsIni(),
          ));
          $this->tpl->assign(array(
            'ORDER_TRAN_TIME' => $item->purchaseTime,
            'ORDER_TRAN_CODE' => $item->tranCode,
            'ORDER_TRAN_TOTAL' => $item->totalAmount,
            'ORDER_TRAN_DATA' => $this->tpl->parse(false, 'details_cell')
          ));

          $this->tpl->parse('ORDER_TRANS_ROWS', 'row', true);
        }
      }

      $this->content = $this->tpl->parse(false, 'table');

    }catch(Exception $e){
      $this->log()->error($e->getMessage());
      $this->content = $e->getMessage();
    }
    return $this->content;
  }
}
?>
