<?php
namespace Verba\Act\MakeList\Worker;

use \Verba\Act\MakeList\Worker;

class CustomOrder extends Worker{

  public $jsScriptFile = 'CustomOrder';

  protected $options = array();
  protected $select;
  public $alias = 'co';

  function init(){
    $this->parent->listen('cfgOrderAfter', 'run', $this, 'CustomOrder_run');
    $v = \Verba\Lang::get('products list sort custom values');
    if(is_array($v)){
      $this->options = $v;
    }
  }

  function run(){

    $this->parseSelect();


    $qm = $this->parent->QM();
    $alias = 'custom_order';
    if($qm->getOrder($alias)){
      $qm->removeOrder($alias);
    }
    $case = $this->parent->rq($this->alias);
    if(!$case || !in_array($case, array_keys($this->options))){
      return;
    }

    switch($case){
      case 'new':
        $qm->addOrder(array($this->parent->oh()->getPAC() => 'd'), $alias);
        break;
      case 'pop':
        break;
      case 'comm':
        $qm->addOrder(array('comments_count' => 'd'), $alias);
        break;
      case 'cheap':
        $qm->addOrder(array('price' => 'a'), $alias);
        break;
      case 'expensive':
        $qm->addOrder(array('price' => 'd'), $alias);
        break;
      case 'promos':
        $_promo = \Verba\_oh('promotion');
        list($promoA, $promoT, $promoDb) = $qm->createAlias($_promo->vltT());
        $qm->addOrder('`promos` DESC');
        break;
    }
    return true;
  }

  function parseSelect(){
    $this->select = new \Html\Select();
    $this->select->addClasses('custom-list-order  custom-list-order-'.$this->alias.' list-order-'.$this->parent->getID());
    $this->select->setValues($this->options);
    $qm = $this->parent->QM();
    $rqValue = $this->parent->rq($this->alias);
    $cvalue = isset($rqValue) ? (string)$rqValue : false;
    if($cvalue){
      $this->select->setValue($cvalue);
    }
    return $this->select;
  }

  function getSelect(){
    if($this->select === null){
      $this->select = false;
      $this->parseSelect();
    }
    return $this->select;
  }
}
?>