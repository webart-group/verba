<?php

namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class Integer extends Around
{
    function run()
    {
        if (!isset($this->value) || false === $this->value) {
            return $this->value;
        }

        return (int)$this->value;
    }
}
