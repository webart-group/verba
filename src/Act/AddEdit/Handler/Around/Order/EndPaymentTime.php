<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use Act\AddEdit\Handler\Around;

class EndPaymentTime extends Around
{
    function run()
    {
        if($this->action == 'edit'){
            $endTime = strtotime($this->value);
            if(!$endTime || $endTime == $this->getExistsValue($this->A->getCode())){
                return null;
            }
        }else{
            $paysys = \Verba\_mod('payment')->getPaysys($this->ah->getTempValue('paysysId'));
            if(!$paysys ){
                $this->log()->error('Unable to get Order Paysys Awaiting Time');
                return false;
            }
            if(!$paysys->payment_awaiting){
                return null;
            }
            $endTime = time()+$paysys->payment_awaiting;
        }

        return date('Y-m-d H:i:s', $endTime);
    }
}
