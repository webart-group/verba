<?php

namespace Verba\Act\AddEdit\Handler\Around\Ledger;

use Act\AddEdit\Handler\Around;

class AmountBase extends Around
{
    function run()
    {
        if($this->action == 'edit'){
            if(!isset($this->tempData['currencyId'])
                || $this->ah->getTempValue('currencyId') == $this->getExistsValue('currencyId')){
                $rate = $this->getExistsValue('rate');
            }else{
                $cur = \Verba\_mod('currency')->getCurrency($this->ah->getTempValue('currencyId'));
            }
        }else{
            $cur = \Verba\_mod('currency')->getCurrency($this->ah->getTempValue('currencyId'));
        }

        if(!isset($rate)){
            if(!$cur){
                $this->log()->error('Currency not found for curId: '.var_export($this->ah->getTempValue('currencyId'),true));
                return false;
            }
            $rate = $cur->p('rate');
        }

        return round($this->ah->getTempValue('amount') / $rate, 2);
    }
}
