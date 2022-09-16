<?php

namespace Verba\Act\AddEdit\Handler\Around\Withdrawal;

use \Verba\Act\AddEdit\Handler\Around;

class TaxOut extends Around
{
    function run()
    {

        $sum = $this->ah->getTempValue('sum');

        if($sum === null){
            return null;
        }

        $curOutId = $this->ah->getTempValue('currencyId');
        $psOutId = $this->ah->getTempValue('paysysId');

        if(!$curOutId || !$psOutId){
            $this->log()->error('Unable to calculate tax out - bad params'
                . '$sum: ' . var_export($sum, true)
                . '$curOutId: ' . var_export($curOutId, true)
                . '$psOutId: ' . var_export($psOutId, true)
            );
            return false;
        }

        $Cur =  \Verba\Mod\Currency::getInstance()->getCurrency($curOutId);

        $kOut = (float)$Cur->getPaysysLinkValue('output', $psOutId, 'kOut');

        $taxOut = \Verba\reductionToCurrency($sum / 100 * $kOut);

        return $taxOut;
    }
}
