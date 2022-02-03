<?php
class balop_cronCompletedOrdersHoldTillRelease extends \Verba\Block {

  function build(){

    $_balop = \Verba\_oh('balop');
    $_order = \Verba\_oh('order');
    $nowdate = date('Y-m-d H:i:s');
    $q = "SELECT `id`, `accountId`, `primitiveId`
    FROM ".$_balop->vltURI()."
    
    WHERE
    `block` = 1
    && `holdTill` IS NOT NULL
    && `holdTill` <= '".$nowdate."'
    && `unblocked` IS NULL
    && `primitiveOt` = 89

    ORDER BY `holdTill` ASC
    
    LIMIT";

    $nr = $step = 100;
    $mOrder = \Mod\Order::i();
    for($i = 0; $nr == $step; $i = $i+$step) {
      $qc = $q . ' ' . $i . ',' . $step;
      $sqlr = $this->DB()->query($qc);
      $nr = $sqlr->getNumRows();
      if (!$nr) {
        break;
      }
      while ($row = $sqlr->fetchRow()) {
        $Acc = new \Mod\Account\Model\Account($row['accountId']);
        if($mOrder->finalOrderSellerGravity($row['primitiveId'],$Acc)){
          $this->DB()->query("UPDATE ".$_balop->vltURI()." SET `unblocked` = '".date('Y-m-d H:i:s')."' WHERE id = '".$row['id']."'");
          // Обновление поля sumHoldTill у Заказа
          $this->DB()->query("UPDATE ".$_order->vltURI()." SET sumHoldTill = NULL WHERE id = '".$row['primitiveId']."'");
        }
      }


    }


    return array(
      2,
      array(
        'startAt' => date('Y-m-d H:i:s', strtotime("+1 minute"))
      )
    );
  }

}
?>