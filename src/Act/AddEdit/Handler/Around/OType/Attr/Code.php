<?php

namespace Verba\Act\AddEdit\Handler\Around\OType\Attr;

use Act\AddEdit\Handler\Around;

class Code extends Around
{
    function run()
    {
        if(!isset($this->value)) {
            return $this->value;
        }
        if($this->action == 'edit'
            && $this->getExistsValue('attr_code') === $this->value){
            return $this->value;
        }
        $this->value = trim($this->value);
        $this->value = preg_replace("/[^a-z0-9_]/i", '_', $this->value);
        if(!preg_match("/^[a-z_]+[a-z_0-9]+/i", $this->value)){
            $this->log()->error('Attribute Code does not match syntax rule. Olny a-z0-9_ and first char must be a a-z_');
            return false;
        }

        return $this->value;
    }
}
