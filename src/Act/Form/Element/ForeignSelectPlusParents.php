<?php
namespace Verba\Act\Form\Element;

class ForeignSelectPlusParents extends ForeignSelect
{
  /**
   * @var array $parents Array of parents where $parents array(ot1 => array(id1, id2..)[, ... ])
   */
  public $parents = null;

  function modifyQuery($qm){

    if(!$this->parents || !is_array($this->parents) || !count($this->parents)){

      throw  new \Verba\Exception\Building(__CLASS__.': Unknown parent');

    }

    foreach($this->parents as $pot => $piids){

      $qm->addConditionByLinkedOTRight($pot,$piids);

    }
    return $qm;
  }
}
