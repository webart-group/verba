<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use Act\AddEdit\Handler\Around;

class ConfirmedBuyer extends Around
{
    function run()
    {
        if($this->value === null
            || $this->value == $this->ah->getExistsValue($this->A->getCode())){
            return $this->value;
        }

        if($this->value == 1){
            if(!is_string($action_script_sign = $this->ah->getExtendedData('__buyer_confirm_script_key'))
                || $action_script_sign !== SYS_SCRIPT_KEY
                || $this->ah->getActualValue('payed') != 1
                || !is_object($U = $this->ah->getExtendedData('ProfileU'))
                || $this->ah->getActualValue('owner') != $U->getID())
            {
                return null;
            }
        }

        return $this->value;
    }
}
