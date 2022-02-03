<?php

namespace Mod\Balop\Cause;

class OrderPayedCanceledCashbackBuyerGravityFinal extends Ordered
{
    protected $otype = 'balop';
    protected $block = 0;
    protected $_itemClassSuffixRequired = 'OrderPayedCanceledCashbackSellerEase';

    function calcSum()
    {
        if(!$this->Order || !$this->Order instanceof \Mod\Order\Model\Order){
            return 0;
        }

        return $this->Order->getByuerSum();
    }

    function getDescription()
    {
        $r = \Verba\Lang::get('balop descriptions canceledReturned', array(
            'order_code' => $this->Order->getCode(),
        ));
        return $r;
    }
}