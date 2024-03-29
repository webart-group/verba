<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use \Verba\Act\AddEdit\Handler\Around;

class Discount extends Around
{
    function run()
    {
        if($this->action != 'new'
            || ($this->action == 'new' && $this->value)){
            return $this->value;
        }
        /**
         * @var $Cart \Verba\Mod\Cart\CartInstance
         */
        $Cart = $this->ah->getExtendedData('cart');
        $Cart->getPromos();

        return $Cart->getDiscount();
    }
}
