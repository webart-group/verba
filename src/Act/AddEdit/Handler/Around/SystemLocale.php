<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class SystemLocale extends Around
{
    function run()
    {
        if($this->action == 'edit'){
            return $this->value;
        }
        $this->value = \Verba\Lang::$locale;
        return $this->value;
    }
}
