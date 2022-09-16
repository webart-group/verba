<?php

namespace Verba\Act\AddEdit\Handler\Around\Account;

use \Verba\Act\AddEdit\Handler\Around;

class ActiveCheckByCurrencyState extends Around
{
    function run()
    {

        if($this->value === null){
            return $this->value;
        }

        $curId = $this->ah->getActualValue('currencyId');

        $mCurrency = \Verba\Mod\Currency::getInstance();
        $cur = $mCurrency->getCurrency($curId);

        if(!$cur /*|| !$cur->active*/){ // валюта отстутвует или неактивна
            return 0;
        }
        return $this->value;
    }
}
