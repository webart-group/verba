<?php
namespace Verba\Act\MakeList\Handler\Field;

use Act\MakeList\Handler\Field;

class MakeStrftimetime extends Field{

  function run(){
    return $this->list->oh()->ph_make_strftimetime_handler($this->attr_code, $this->list->row);
  }

}
