<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class StripTags extends Around
{
    function run()
    {
        if(!isset($this->value)){
            return null;
        }
        $this->value = (string)$this->value;
        return strip_tags($this->value);
    }
}
