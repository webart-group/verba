<?php

namespace Verba\Act\AddEdit\Handler\Around\Account;

use \Verba\Act\AddEdit\Handler\Around;

class Balance extends Around
{
    function run()
    {
        if($this->action == 'new'){
            return 0;
        }
        return null;
    }
}
