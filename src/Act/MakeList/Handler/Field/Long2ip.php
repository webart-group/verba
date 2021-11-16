<?php
namespace Verba\Act\MakeList\Handler\Field;

use Act\MakeList\Handler\Field;

class Long2ip extends Field {

  function run(){
    return $this->list->oh()->ph_long2ip_handler($this->attr_code, $this->list->row);
  }

}
