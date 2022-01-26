<?php

namespace Verba\Act\AddEdit\Handler\Around\OType\Attr;

use \Verba\Act\AddEdit\Handler\Around;

class Predefined extends Around
{
    function run()
    {

        if($this->action == 'edit'){
            return $this->value;
        }

        $fe = $this->ah->getTempValue('form_element');
        $r = null;
        if($fe == 'select'){
            $r = 32;
        }

        return $r;
    }
}
