<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use Act\AddEdit\Handler\Around;

class DiscountPercent extends Around
{
    function run()
    {
        $total = (float)$this->ah->getTempValue('total');
        $discount = (float)$this->ah->getTempValue('discount');

        $r = (float)($discount / $total * 100);

        return $r;
    }
}
