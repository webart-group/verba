<?php
class order_acpListHandlerOrderTotal extends ListHandlerField {

  function run(){

    $str = \Verba\reductionToCurrency($this->list->row['total'] * $this->list->row['rate']);
    if(isset($this->list->row['discount']) && $this->list->row['discount'] > 0){
      $str .= '<div class="order-discount-value">-'.\Verba\reductionToCurrency($this->list->row['discount'] * $this->list->row['rate']).'</div>';
    }

    return $str;
  }

}
?>