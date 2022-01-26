<?php
namespace Verba\Act\MakeList\Handler\Field;

use \Verba\Act\MakeList\Handler\Field;

class Predefined extends Field {

  function run(){
    return $this->list->oh()->ph_exp_predefined_handler($this->attr_code, $this->list->row);
  }

}
