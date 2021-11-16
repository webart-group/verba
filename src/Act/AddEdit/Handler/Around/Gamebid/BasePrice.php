<?php

namespace Verba\Act\AddEdit\Handler\Around\Gamebid;

use Act\AddEdit\Handler\Around;

class BasePrice extends Around
{
    function run()
    {
        $currencyId = $this->ah->getActualValue('currencyId');
        $price = $this->ah->getActualValue('price');
        /**
         * @var $mCur \Currency
         */
        $mCur = \Verba\_mod('currency');
        $currency = $mCur->getCurrency($currencyId);

        if(!$price || $currency){
            return $price;
        }
        $rate = $currency->p('rate');
        if($rate < 1){
            $val = $price / $rate;
        }else{
            $val = $price * $rate;
        }

        return $val;
    }
}
