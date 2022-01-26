<?php
namespace Verba\Act\MakeList\Handler\Field;

use \Verba\Act\MakeList\Handler\Field;

class Logic extends Field{

  function run(){
    if(!is_array($this->list->fieldCfg['attr'])){
      $this->list->fieldCfg['attr'] = array();
    }
    $this->list->fieldCfg['attr']['data-value'] = $this->list->row[$this->attr_code];
    return $this->list->oh()->ph_logic_handler($this->attr_code, $this->list->row);
  }

}
