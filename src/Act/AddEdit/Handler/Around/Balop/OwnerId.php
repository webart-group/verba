<?php

namespace Verba\Act\AddEdit\Handler\Around\Balop;

use \Verba\Act\AddEdit\Handler\Around;

class OwnerId extends Around
{
    function run()
    {
        if($this->action != 'new'){
            return null;
        }
        $Acc = new \Verba\Mod\Account\Model\Account($this->ah->getTempValue('accountId'));

        if(!$Acc || !$Acc->getId()){
            return false;
        }

        return $Acc->owner;
    }
}
