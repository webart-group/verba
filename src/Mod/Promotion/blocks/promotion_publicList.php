<?php
class promotion_publicList extends \Verba\Block\Html{

  public $list;

  function init(){

    $this->addCss(array(
      array('promotion')
    ));

  }

  function build(){

    $_promo = \Verba\_oh('promotion');

    $this->list = $_promo->initList(array(
      'cfg' => 'public promotion',
      'block' => $this,
    ));

    $qm = $this->list->QM();
    list($a, $t, $db) = $qm->createAlias();

    $qm->addWhere(1, 'active');
    $qm->addWhere(0, 'hidden');

    $this->content = $this->list->generateList();
    $q = $qm->getQuery();
    return $this->content;
  }
}
?>
