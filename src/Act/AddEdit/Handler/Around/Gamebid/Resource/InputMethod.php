<?php

namespace Verba\Act\AddEdit\Handler\Around\Gamebid\Resource;

use \Verba\Act\AddEdit\Handler\Around;

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
