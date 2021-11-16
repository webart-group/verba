<?php
namespace Verba\Act\MakeList\Filter;

class VariantsMulti extends VariantsBase {

  use AttrMulti;

  function requestAvaibleOptions(){

    $qm = $this->WD->getQM();
    //$qm = clone $qm;
    list($a, $t, $db) = $qm->createAlias();
    $q =
      "SELECT
  var_id as `id`,
  COUNT(iid) as `count`
FROM
  `".SYS_DATABASE."`.`attr_multiples`
WHERE
  attr_id = ".$this->A->getID()."
  && iid IN(
SELECT ".$a.".`".$this->list->oh()->getPAC()."` FROM `".$db."`.`".$t."` ".$a."
".$qm->compiledCJoin."
WHERE
".$qm->compiledWhere."
)
GROUP BY var_id";

    $sqlr = $this->DB()->query($q);

    if($sqlr && $sqlr->getNumRows()){
      while($row = $sqlr->fetchRow()){
        $this->avaible[$row['id']] = $row['count'];
      }
    }
  }
}
?>