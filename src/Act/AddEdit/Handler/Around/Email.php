<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Email extends Around
{
    function run()
    {
        if(!isset($this->value)){
            return null;
        }
        settype($this->value, 'string');
        $tf = new \Verba\Data\Email(array());
        $tf->setValue($this->value);
        if(!$tf->validate()){
            return false;
        }
        return $tf->getValue();
    }
}
