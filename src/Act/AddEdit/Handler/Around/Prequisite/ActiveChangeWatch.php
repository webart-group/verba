<?php

namespace Verba\Act\AddEdit\Handler\Around\Prequisite;

use \Verba\Act\AddEdit\Handler\Around;

class ActiveChangeWatch extends Around
{
    function run()
    {
        $curId = $this->ah->getActualValue('currencyId');
        $psId = $this->ah->getActualValue('paysysId');

        /**
         * @var $mCurrency \Currency
         * @var $mPaysys \Paysys
         */
        $mCurrency = \Verba\_mod('currency');

        $cur = $mCurrency->getCurrency($curId);
        $ps = \Verba\_mod('payment')->getPaysys($psId);

        if(!$cur || !$cur->p('active')){ // валюта отстутвует или неактивна
            $this->log()->error('Currency not found or inactive');
            return 0;
        }
        if(!$ps || !$ps->active) { // платежка отстутвует или неактивна
            $this->log()->error('Paysystem not found or inactive');
            return 0;
        }
        // платежки нет у валюты в парах на вывод
        if( !$cur->isPaysysLinkExists($ps->id, 'output')){
            $this->log()->error('Paysystem not avaible for this currency');
            return 0;
        }
        return $this->value;
    }
}
