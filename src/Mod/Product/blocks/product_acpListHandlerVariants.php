<?php
class product_acpListHandlerVariants extends ListHandlerField{

  use product_listHandlerField;

  protected $cur;

  function run(){
    if(empty($this->list->row['variant'])){
      return '';
    }

    $this->tpl->define(array(
      'wrap' => 'product/acp/list/variants.tpl',
      'item' => 'product/acp/list/variants_item.tpl',
    ));
    if($this->cur === null){
      $this->cur = \Verba\_mod('currency')->getBaseCurrency();
    }
    $cv = explode('#', $this->list->row['variant']);

    $this->tpl->assign(array(
      'VAR_CURRENCY_SHORT' => $this->cur->short,
      'VARS_COUNT' => count($cv),
    ));
    $i = 0;
    foreach($cv as $v){
      if($i++ == 2){
        break;
      }
      $v = explode(':', $v);
       $this->tpl->assign(array(
        'VAR_IID' => $v[0],
        'VAR_PRICE' => \Verba\reductionToCurrency($v[1] * $this->cur->rate),
        'VAR_SIZE' => $v[2],
        //'VAR_UNIT' => $v[3],
        'VAR_UNIT' => (string)$this->getSizeUnitPdValue($v[3]),
        'VAR_ARTICUL' => $v[4],
       ));
      $this->tpl->parse('VARS_ITEMS', 'item', true);
    }

    return $this->tpl->parse(false, 'wrap');
  }

}
?>