<?php
namespace Verba\Act\MakeList\Filter;
trait AttrMulti{

  function applyValue(){

    $whereAlias = $this->makeWhereAlias();
    $joinAlias = $this->makeWhereAlias().'_j';
    $qm = $this->list->QM();

    $qm->removeWhere($whereAlias);
    $qm->removeCJoin($joinAlias);
    $this->WD->removeWhere($whereAlias);
    $this->WD->removeJoin($joinAlias);

    if(isset($this->value) && !empty($this->value)){
      list($a) = $qm->createAlias();
      list($am) = $qm->createAlias('attr_multiples', SYS_DATABASE, 'flt_'.$this->name);
      $oh = $this->list->oh();

      $qm->addCJoin(array(array('a' => $am)),
        array(
          array('p' => array('a'=> $am, 'f' => 'iid'),
            's' => array('a'=> $a, 'f' => $oh->getPAC())),
          array('p' => array('a'=> $am, 'f' => 'ot_id'),
            's' => $oh->getID()),
        ), false, $joinAlias, 'INNER'
      );

      $this->WD->addJoin($joinAlias);
      $qm->addWhere($am.".attr_id=".$this->A->getID()." &&" . $this->DB()->makeWhereStatement($this->value, 'var_id', $am), false, $whereAlias);
      $this->WD->addWhere($whereAlias);
    }
  }

}