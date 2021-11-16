<?php
namespace Verba\Act\MakeList\Filter;

class WorkingData {

  public $list;
  /**
   * @var \Verba\QueryMaker
   */
  public $qm;
  public $join = array();
  public $where = array();
  /**
   * @var Controller
   */
  public $C;

  function __construct($C)
  {
    $this->C = $C;
    $this->list = $this->C->getList();
  }

  function getQM(){

    if($this->qm === null){
      $this->qm = clone $this->list->QM();
      $this->qm->setCount(true);
      $this->qm->makeQuery();
    }

    return $this->qm;
  }

  function addJoin($alias){
    return $this->join[$alias] = $alias;
  }
  function removeJoin($alias){
    if(array_key_exists($alias, $this->join)){
      unset($this->join[$alias]);
      return true;
    }
    return null;
  }
  function getJoin(){
    return $this->join;
  }

  function getWhere(){
    return $this->where;
  }
  function addWhere($alias){
    return $this->where[$alias] = $alias;
  }
  function removeWhere($alias){
    if(array_key_exists($alias, $this->where)){
      unset($this->where[$alias]);
      return true;
    }
    return null;
  }

  /**
   * @param $qm \Verba\QueryMaker
   * @return mixed
   */
  function clearQmFromFilters($qm){
    if(count($this->join)){
      foreach($this->join as $joinAlias){
        $qm->removeCJoin($joinAlias);
      }
    }
    if(count($this->where)){
      foreach($this->where as $whereAlias){
        $qm->removeWhere($whereAlias);
      }
    }
    return $qm;
  }
}
?>