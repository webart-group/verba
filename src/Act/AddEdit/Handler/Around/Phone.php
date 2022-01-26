<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Phone extends Around
{
    function run()
    {
        if(!isset($this->value)){
            return null;
        }
        settype($this->value, 'string');
        $this->value = preg_replace("/[\D]/", '', $this->value);
        return $this->value;
    }
}
