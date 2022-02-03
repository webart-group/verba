<?php

namespace Mod\Balop\Cause;

use Mod\Balop\Cause;

class WithdrawalEase extends Cause{

    protected $otype = 'withdrawal';
    protected $block = 0;
    /**
     * @var \Verba\Model\Currency
     */
    protected $Cur = null;

    function init()
    {
        $this->Cur =  \Mod\Currency::getInstance()->getCurrency($this->_i->currencyId);
        $this->getSum();
    }

    function calcSum(){

        $requested_sum = is_object($this->_i) ? $this->_i->getValue('sum') : 0;
        if(!$requested_sum || $requested_sum < 0){
            return 0;
        }

        return $requested_sum * -1;
    }

    function getCurrencyId(){
        return is_object($this->_i) ? $this->_i->getRawValue('currencyId') : null;
    }

    function getDescription()
    {
        $this->getSum();

        $langKey = $this->_i->taxOut < 0
            ? 'Bonus'
            : 'Tax';

        return \Verba\Lang::get('withdrawal balopDescriptions withdrawalAs'.$langKey, array(
            'ps_tax' => \Verba\reductionToCurrency(abs($this->_i->taxOut)),
            'cur_symbol' => $this->Cur->symbol
        ));
    }

}