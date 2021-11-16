<?php
namespace Verba\Act\MakeList\Handler\Field;

use Act\MakeList\Handler\Field;

class ForeignId extends Field{

  function run(){
    return $this->list->oh()->ph_foreign_id_handler($this->attr_code, $this->list->row);
  }

}
