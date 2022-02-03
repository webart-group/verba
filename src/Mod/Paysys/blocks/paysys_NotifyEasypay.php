<?php
class paysys_NotifyEasypay extends paysys_Notify{

  function build(){
    $orderId = $this->request->iid;
    $this->content = '';
    try{

      $n = new PayNotify_Easypay($orderId);
      $mod = $n->getPaysysMod();
      switch($n->status){
        case 'success':
          $this->log()->event($mod->getPsCode().": Payment is successful. Order Id:".$n->orderId);
          break;
        case 'error':
          $this->log()->error($mod->getPsCode().": Payment Notify error. Status: ".$n->status.", Order Id:".$n->orderId);
          break;
        case 'not_valid':
          $this->log()->error($mod->getPsCode().": Payment Result not valid. Status: ".$n->status.", Order Id:".$n->orderId);
          break;
        default:
          $this->log()->error($mod->getPsCode().": Notify response unknown status".var_export($n, true));
          break;
      }

      $ae = $mod->updateOrderStatus($n);
      if($ae->haveErrors()){
        $this->content = 0;
      }else{
        $this->content = 1;
      }

    }catch(Exception $e){
      $this->log()->error($e->getMessage());
      $this->content = $e->getMessage();
    }

    return $this->content;
  }


}

?>
