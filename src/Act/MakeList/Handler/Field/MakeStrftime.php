<?php
namespace Verba\Act\MakeList\Handler\Field;

use \Verba\Act\MakeList\Handler\Field;

class MakeStrftime extends Field{

  function run(){
    return $this->list->oh()->ph_make_strftime_handler($this->attr_code, $this->list->row);
  }

}
