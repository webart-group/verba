<?php

namespace Verba\Act\AddEdit\Handler\Around\Withdrawal;

use \Verba\Act\AddEdit\Handler\Around;

class Sum extends Around
{
    function run()
    {
        if($this->action == 'edit'){
            return null;
        }

        $this->value = (float)$this->value;

        $Acc = \Verba\User()->Accounts()->getAccount($this->ah->getTempValue('accountId'));

        if($this->value <=0
            || $Acc->balance <= 0
            || $this->value > $Acc->balance){
            $this->log()->error('Bad sum required');
            return false;
        }

        return $this->value;
    }
}
