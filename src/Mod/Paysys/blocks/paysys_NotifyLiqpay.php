<?php
class paysys_NotifyLiqpay extends paysys_Notify{

  function build(){
    $orderId = $this->request->iid;
    try{
      $n = new PayNotify_Liqpay($orderId);
      $mod = $n->getPaysysMod();
      if($n->isValid()){
        switch($n->status){
          case 'success':
            $this->log()->event($mod->getPsCode().": Transaction is successful. Order Id:".$orderId);
            break;
          case 'wait_secure':
            $this->log()->warning($mod->getPsCode().": Transaction is on secure wait. Order Id:".$orderId);
            break;
          case 'failure':
            $this->log()->error($mod->getPsCode().": Trasaction is failure. Code: ".(string)$n->code.", Order Id:".$orderId);
            break;
          default:
            $this->log()->error($mod->getPsCode().": Notify response unknown status".var_export($n, true));
            break;
        }
      }else{
        $this->log()->error($mod->getPsCode().": Notify response is invalid \n".var_export($n, true));
      }
      $n->updateTransactionByNotify();
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
