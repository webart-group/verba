<?php
class order_acpListHandlerSubstId extends ListHandlerField {

  function run(){

    if(!$this->tpl->isDefined('time-cell')) {
      $this->tpl->define(array(
        'subst_id' => 'shop/order/list/subst_id.tpl',
      ));
    }

    $this->tpl->assign(array(
      'CLASS' => 'list-button-item-edit',
      'HREF' => var2url($this->list->getEditUrl(), array('iid' => $this->list->row[_oh('order')->getPAC()])),
      'ITEM_ID' => $this->list->row['code'],
    ));
    return $this->tpl->parse(false, 'subst_id');
  }

}
?>