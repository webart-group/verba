<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class SecToHours extends Around
{
    function run()
    {
        $this->value = (int)$this->value;
        return $this->value && $this->value > 0 ? $this->value * 3600 : $this->value;
    }
}
