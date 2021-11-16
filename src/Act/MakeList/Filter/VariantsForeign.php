<?php
namespace Verba\Act\MakeList\Filter;

class VariantsForeign extends VariantsBase {

  function applyValue(){

    $whereAlias = $this->makeWhereAlias();
    $qm = $this->list->QM();

    $qm->removeWhere($whereAlias);
    if(isset($this->value) && !empty($this->value)){
      list($a) = $qm->createAlias();
      $qm->addWhere($this->DB()->makeWhereStatement($this->value, $this->A->getCode(), $a), false, $whereAlias);
    }
  }

  function requestAvaibleOptions(){
    $qm = $this->WD->getQM();
    $qm = clone $qm;
    $qm = $this->WD->clearQmFromFilters($qm);
    $qm->makeQuery();

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

    //$this->avaible = $this->getValues();
  }

  function getValues(){
    if($this->values === null){
      $this->values = array();
      $foh = \Verba\_oh($this->A->getForeignOtId());
      $fattr = $this->A->getForeignAttrId();
      $attrCode = $foh->A($fattr)->getCode();

      $qm = clone($this->list->QM());
      $qm = $this->WD->clearQmFromFilters($qm);
      $qm->makeQuery();
      list($primA, $t, $db) = $qm->createAlias();

      $obligatory_join = $qm->compileJoinBySign('obligatory');

      $fqm = new \Verba\QueryMaker($foh, false, array($foh->A($fattr)->getCode()), null, null, array($foh->vltT(), $foh->vltDB(), 'b11'));
      list($fltA, $tb, $dbb) = $fqm->createAlias();
      $fqm->addOrder(array('priority' => 'd', $attrCode=> 'a'));
      $fqm->addWhere(1, 'active', false, array($tb, $dbb, $fltA));
      $fqm->makeQuery();

      $q = "
SELECT
".$fqm->compiledSelect."
FROM `".$db."`.`".$t."` ".$primA."
".$obligatory_join."
LEFT JOIN `".$dbb."`.`".$tb."` ".$fltA."
ON `".$fltA."`.`".$foh->getPAC()."` = `".$primA."`.`".$this->A->getCode()."`
WHERE  ".(!empty($qm->compiledWhere) ? $qm->compiledWhere.' && ' : '' ).$fqm->compiledWhere."
GROUP BY `".$primA."`.`".$this->A->getCode()."`
ORDER BY ".$fqm->compiledOrder."
";
      $sqlr = $this->DB()->query($q);

      $pac = $foh->getPAC();
      while($row = $sqlr->fetchRow()){
        $this->values[$row[$pac]] = $row[$attrCode];
      }
    }
    return $this->values;
  }
}
?>