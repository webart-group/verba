<?php

namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class GameActiveGuest extends Around
{
    function run()
    {
        if(!$this->ah->getUser()->email_confirmed){
            return 0;
        }

        return $this->value;
    }
}
