<?php
namespace Verba\Act\MakeList\Handler\Field;

use Act\MakeList\Handler\Field;

class Unserialize extends Field {

  function run(){
    return $this->list->oh()->ph_unserialize_handler($this->attr_code, $this->list->row);
  }

}
