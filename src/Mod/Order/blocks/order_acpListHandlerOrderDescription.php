<?php
class order_acpListHandlerOrderDescription extends ListHandlerField {

  function run(){
    return nl2br($this->list->row['description']);
  }

}
?>