<?php
namespace Verba\Act\MakeList\Filter;

class VariantsPredefined extends VariantsBase {

  function applyValue(){

    $whereAlias = $this->makeWhereAlias();
    $qm = $this->list->QM();
    $qm->removeWhere($whereAlias);
    $this->WD->removeWhere($whereAlias);
    if(isset($this->value) && !empty($this->value)){
      $this->WD->addWhere($whereAlias);
      list($a) = $qm->createAlias();
      $qm->addWhere($this->DB()->makeWhereStatement($this->value, $this->attr, $a), false, $whereAlias);
    }
  }

  function requestAvaibleOptions(){

    $qm = $this->WD->getQm();
    //$qm = clone $qm;
    list($a, $t, $db) = $qm->createAlias();
    $q =
      "SELECT
  ".$a.".`".$this->A->getCode()."` as `id`,
  COUNT(".$a.".`".$this->list->oh()->getPAC()."`) as `count`
FROM `".$db."`.`".$t."` ".$a."
".$qm->compiledCJoin."
WHERE
".$qm->compiledWhere."
GROUP BY ".$a.".`".$this->A->getCode()."`";

    $sqlr = $this->DB()->query($q);

    if($sqlr && $sqlr->getNumRows()){
      while($row = $sqlr->fetchRow()){
        $this->avaible[$row['id']] = $row['count'];
      }
    }
  }

}
?>