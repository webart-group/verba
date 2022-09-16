<?php

namespace Verba\Mod\Balop\Cause;

class OrderPayedSellerGravityFinal extends Ordered
{

    protected $otype = 'balop';
    protected $block = 0;
    protected $_itemClassSuffixRequired = 'OrderPayedSellerEase';

    function calcSum()
    {
        return abs($this->_i->sumout);
    }

    function getDescription()
    {
        $r = \Verba\Lang::get('balop descriptions orderPayedSellerGravityFinal', array(
            'order_code' => $this->Order->getCode(),
        ));
        return $r;
    }
}