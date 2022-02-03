<?php

namespace Mod\Balop\Cause;

use Mod\Balop\Cause;

class WithdrawalCanceledGravity extends Cause{

    protected $otype = 'balop';
    protected $_itemClassSuffixRequired = 'WithdrawalEase';
    protected $block = 0;

    function calcSum(){
        return is_object($this->_i) ? abs($this->_i->getValue('sum')): 0;
    }

    function getDescription()
    {
        $this->getSum();

        $langKey = $this->_i->taxOut < 0
            ? 'Bonus'
            : 'Tax';

        return \Verba\Lang::get('withdrawal balopDescriptions canceled'.$langKey, array(
            'ps_tax' => \Verba\reductionToCurrency(abs($this->_i->taxOut)),
            'cur_symbol' => $this->Cur->symbol
        ));
    }
}