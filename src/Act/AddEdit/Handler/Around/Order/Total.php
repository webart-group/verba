<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use \Verba\Act\AddEdit\Handler\Around;

class Total extends Around
{
    function run()
    {
        $total = $this->ah->getExtendedData('total');
        if(isset($total)){
            return (float)$total;
        }

        $Cart = $this->ah->getExtendedData('cart');
        /**
         * @var $mShop Shop
         */
        $total = $Cart->getTotal();

        return !$total || $total < 0 ? false : $total;
    }
}
