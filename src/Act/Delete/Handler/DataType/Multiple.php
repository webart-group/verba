<?php

namespace Verba\Act\Delete\Handler\DataType;

use Act\Delete\Handler;

class Multiple extends Handler
{
    function run()
    {
        $q = "DELETE FROM `".SYS_DATABASE."`.`attr_multiples` WHERE
      `ot_id` = '".$this->oh->getID()."'
      && `attr_id` = '".$this->A->getId()."'
      && `iid` = '".$this->row[$this->oh->getPAC()]."'";
        $this->DB()->query($q);
    }
}
