<?php

namespace Verba\Act\AddEdit\Handler\Around\Gamebid\Resource;

use Act\AddEdit\Handler\Around;

class InputMethod extends Around
{
    function run()
    {
        $this->value = in_array($this->value, array('amount', 'cost'))
            ? $this->value
            : 'amount';
        return $this->value;
    }
}
