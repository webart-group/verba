<?php
//unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class Unique extends Around
{
  function run()
  {
    if(!isset($this->value)){
      return null;
    }
    $attr_code = $this->A->getCode();
    $this->value = trim($this->value);

    $qm = new \Verba\QueryMaker($this->oh->getID(), $this->oh->getBaseKey(), array($attr_code));
    $qm->addWhere("`".$attr_code."` = '".$this->DB()->escape_string($this->value)."'");
    if($this->action == 'edit'){
      $qm->addWhere("`".$this->oh->getPAC()."` != '".$this->ah->getIID()."'");
    }
    $qm->addLimit(1);
    $qm->makeQuery();
    $oRes = $this->DB()->query($qm->getQuery());
    if($oRes->getNumRows() == 1){
      $this->log()->error("field '".$attr_code."' is not unique");
      return false;
    }
    return $this->value;
  }
}
