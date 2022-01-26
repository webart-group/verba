<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class CallbackPhone extends Around
{
    function run()
    {
        if($this->action == 'edit'){
            $evalue = $this->getExistsValue($this->A->getCode());
        }else{
            $evalue = null;
        }
        return is_string($this->value) && preg_match("/[0-9]{10,15}/i", $this->value)
            ? $this->value
            : false;
    }
}
