<?php
namespace Verba\Act;

class Parents extends \Verba\Configurable {

  protected $parents = array();
  protected $parentData = array();
  protected $parentsRelation = false;

  function addParents($pot, $piid)
  {
    if(!$pot || !\Verba\isOt($pot)){
      return false;
    }
    $ot_id = \Verba\_oh($pot)->getID();
    if(!array_key_exists($ot_id, $this->parents)){
      $this->parents[$ot_id] = array();
    }
    \Verba\convertToIdList($piid, true);
    $this->parents[$ot_id] = array_merge($this->parents[$ot_id], $piid);

    return $this->parents;
  }

  function getParents(){
    return $this->parents;
  }

  function addMultipleParents($array){
    if(!is_array($array)){
      return false;
    }
    foreach($array as $ot => $iids){
      $this->addParents($ot, $iids);
    }
  }

  function getFirstParent(){
    if(!count($this->parents)){
      return array(false, false);
    }
    reset($this->parents);
    $pot = key($this->parents);
    return array($pot, current($this->parents[$pot]));
  }

  function getFirstParentAll(){
    if(!count($this->parents)){
      return array(false, false);
    }
    reset($this->parents);
    $pot = key($this->parents);
    return array($pot, $this->parents[$pot]);
  }

  function getFirstParentOt(){
    if(count($this->parents)){
      reset($this->parents);
      return key($this->parents);
    }
    return false;
  }

  function getFirstParentIid(){
    if(count($this->parents))
    {
      reset($this->parents);
      return current($this->parents[key($this->parents)]);
    }
    return false;
  }

  function setParentsRelation($rl){
    $this->parentsRelation = $rl;
  }

  function getFirstParentData(){
    list($pot, $piid) = $this->getFirstParent();
    if(!$pot || !$piid){
      return false;
    }

    if(!isset($this->parentData[$pot][$piid])){
      $oh = \Verba\_oh($pot);
      $this->parentData[$pot][$piid] = $oh->getData($piid,1);
    }

    return $this->parentData[$pot][$piid];
  }

  function setPot($val){
    $this->addMultipleParents($val);
  }
}
