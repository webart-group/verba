<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class CurrencyRate extends Around
{
    function run()
    {
        if(!isset($this->tempData['currencyId'])
            || !($cur = \Verba\_mod('currency')->getCurrency($this->ah()->getTempValue('currencyId')))){
            return null;
        }
        return (float)$cur->p('rate');
    }
}
