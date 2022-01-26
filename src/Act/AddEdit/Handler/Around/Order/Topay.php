<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use \Verba\Act\AddEdit\Handler\Around;

class Topay extends Around
{
    function run()
    {
        $topay = $this->ah->getExtendedData('topay');
        if(isset($topay)){
            return (float)$topay;
        }

        $total = (float)$this->ah->getTempValue('total');
        $discount = (float)$this->ah->getTempValue('discount');
        $shipping = (float)$this->ah->getTempValue('shipping');
        $topay = $total - $discount + $shipping;
        return !($topay > 0) ? false : $topay;
    }
}
