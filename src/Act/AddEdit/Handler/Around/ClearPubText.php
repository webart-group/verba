<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class ClearPubText extends Around
{
    function run()
    {
        if (!isset($this->value)) {
            return null;
        }
        $r = strip_tags($this->value);
        $r = htmlentities($r, ENT_QUOTES, 'utf-8');

        return $r;
    }
}
