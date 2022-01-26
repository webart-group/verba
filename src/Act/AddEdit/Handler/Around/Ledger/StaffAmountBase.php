<?php

namespace Verba\Act\AddEdit\Handler\Around\Ledger;

use \Verba\Act\AddEdit\Handler\Around;

class StaffAmountBase extends Around
{
    function run()
    {
        $tempCurrencyId = $this->ah->getTempValue('currencyId');
        if($this->action == 'edit'){

            if(!isset($tempCurrencyId)
                || $tempCurrencyId == $this->getExistsValue('currencyId')
            ) {
                $rate = $this->getExistsValue('rate');
            }else{
                $cur = \Verba\_mod('currency')->getCurrency($tempCurrencyId);
            }
        }else{
            $cur = \Verba\_mod('currency')->getCurrency($tempCurrencyId);
        }

        if(!isset($rate)){
            if(!$cur){
                $this->log()->error('Currency not found for curId: '.var_export($tempCurrencyId,true));
                return false;
            }
            $rate = $cur->p('rate');
        }
        if($rate > 1){
            $r = $this->ah->getTempValue('amount') * $rate;
        }else{
            $r = $this->ah->getTempValue('amount') / $rate;
        }
        return round($r, 2);
    }
}
