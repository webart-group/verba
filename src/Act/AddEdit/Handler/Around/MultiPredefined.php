<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class MultiPredefined extends Around
{
    function run()
    {
        return is_array($this->value)
            ? implode('#!#', $this->value)
            : '';
    }
}