<?php
class order_acpListHandlerOrderTopay extends ListHandlerField {

  function run(){
    return \Verba\reductionToCurrency($this->list->row['topay'] * $this->list->row['rate']);
  }

}
?>