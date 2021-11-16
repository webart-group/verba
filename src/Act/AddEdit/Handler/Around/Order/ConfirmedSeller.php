<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use Act\AddEdit\Handler\Around;

class ConfirmedSeller extends Around
{
    function run()
    {
        if($this->value === null
            || $this->value == $this->getExistsValue($this->A->getCode())){
            return $this->value;
        }

        if($this->value == 1){
            if(!is_string($action_script_sign = $this->ah->getExtendedData('__seller_confirm_script_key'))
                || $action_script_sign !== SYS_SCRIPT_KEY){
                $this->value = 0;
                goto rreturn;
            }
        }
        rreturn:
        return $this->value;
    }
}
