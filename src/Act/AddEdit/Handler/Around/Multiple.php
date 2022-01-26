<?php
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Multiple extends Around
{
  function run()
  {
    if($this->value === null){
      return false;
    }
    if(!is_array($this->value) && $this->value !== '' && $this->value !== false ){
      $this->value = array($this->value);
    }
    //insert to normal table
    $existsValue = $this->getExistsValue($this->A->getCode());
    if($this->action == 'edit' && !empty($existsValue)){
      $q = "DELETE FROM `".SYS_DATABASE."`.`attr_multiples` WHERE
      `ot_id` = '".$this->oh->getID()."'
      && `attr_id` = '".$this->A->getId()."'
      && `iid` = '".$this->ah->getIID()."'";
      $this->DB()->query($q);
    }
    if(!is_array($this->value) || !count($this->value)){
      return '';
    }
    $v = "\n('".$this->oh->getID()."','".$this->A->getId()."','".$this->ah->getIID()."','";
    $va = array();
    foreach($this->value as $cval){
      $va[] = $v.$cval."')";
    }
    $va = implode(',', $va);
    $q = "INSERT INTO `".SYS_DATABASE."`.`attr_multiples`
    (`ot_id`, `attr_id`, `iid`, `var_id`) VALUES ".$va;
    $this->DB()->query($q);

    $r = implode($this->value, ',');
    return $r;
  }
}
