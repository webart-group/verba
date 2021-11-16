<?php

namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class UserInput extends Around
{
    function run()
    {
        if(!isset($this->value)) {
            return null;
        }
        if(!settype($this->value, 'string')) {
            return false;
        }
        $this->value = htmlspecialchars(strip_tags(trim($this->value)), ENT_COMPAT, 'UTF-8');
        return $this->value;
    }
}
