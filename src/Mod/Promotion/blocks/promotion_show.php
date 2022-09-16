<?php
class promotion_show extends \Verba\Block\Html{

  public $list;
  public $item;
  public $oh;

  public $templates = array(
    'content' => '/promotion/show/wrap.tpl'
  );

  function init(){

    $this->addCss(array(
      array('promotion')
    ));

  }

  function route(){
    $this->addItems(array(
      'PROMOTION_PRODUCTS' => new promotion_products($this),
    ));
    return $this;
  }

  function prepare(){
    $this->oh = \Verba\_oh('promotion');
    $this->item = $this->oh->getData($this->request->iid, 1);
  }

  function build(){
    $this->tpl->assign(array(
      'PROMOTION_TITLE' => $this->item['title'],
      'PROMOTION_DESCRIPTION' => $this->item['description'],
      'LIST_SORT_SELECTOR' => $this->items['PROMOTION_PRODUCTS']->optionsBlock->content,
    ));
    $_promo = \Verba\_oh('promotion');
    $mMenu = \Verba\_mod('menu');
    $mMenu->addMenuChain(array(
      'ot_id' => $_promo->getID(),
      $_promo->getPAC() => $this->request->iid,
      'title' => $this->item['title'],
    ));

    $this->content = $this->tpl->parse(false, 'content');
    return $this->content;
  }
}
?>
