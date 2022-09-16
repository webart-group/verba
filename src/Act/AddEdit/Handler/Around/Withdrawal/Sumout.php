<?php

namespace Verba\Act\AddEdit\Handler\Around\Withdrawal;

use \Verba\Act\AddEdit\Handler\Around;

class Sumout extends Around
{
    function run()
    {
        $sum = $this->ah->getTempValue('sum');

        if($sum === null){
            return null;
        }

        $taxOut = $this->ah->getTempValue('taxOut');
        if(!is_numeric($sum)
            || !is_numeric($taxOut)){
            $this->log()->error('Unable to calculate withdrawal sum. Bad params');
            return false;
        }

        if($taxOut === 0){
            $sumOut = $sum;

            // bonus
        }elseif($taxOut < 0){
            $sumOut = $sum + abs($taxOut);

            // tax
        }else{
            $sumOut = $sum - $taxOut;
        }

        $Cur =  \Verba\Mod\Currency::getInstance()->getCurrency($this->ah->getTempValue('currencyId'));

        return $Cur->round($sumOut);
    }
}
