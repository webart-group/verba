<?php
class product_acpListHandlerArticul extends ListHandlerField{

  function run(){
    $this->tpl->define(array(
      'wrap' => 'product/acp/list/articul.tpl',
    ));
    $this->tpl->assign(array(
      'ITEM_ARTICUL' => $this->list->row['articul'],
      'ITEM_IID' => $this->list->row['id'],
    ));

    return $this->tpl->parse(false, 'wrap');
  }
}
?>