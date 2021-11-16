<?php

namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class UserDisplayName extends Around
{
    function run()
    {

        if($this->action == 'edit'){
            return $this->value;
        }
        list($boxname) = explode('@', $this->ah->getTempValue('email'));
        return $boxname;
    }
}
