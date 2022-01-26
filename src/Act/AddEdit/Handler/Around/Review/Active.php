<?php

namespace Verba\Act\AddEdit\Handler\Around\Review;

use \Verba\Act\AddEdit\Handler\Around;

class Active extends Around
{
    function run(){

        $ownerId = $this->ah->getActualValue('owner');

        if(!$ownerId){
            return 0;
        }

        return $this->value;
    }
}
