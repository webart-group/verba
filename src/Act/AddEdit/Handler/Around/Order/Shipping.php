<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use \Verba\Act\AddEdit\Handler\Around;

class Shipping extends Around
{
    function run(){

        $total = $this->ah->getTempValue('total');
        $discount = $this->ah->getTempValue('discount');
        $shippingFreeValue = \Verba\_mod('order')->gC('shipping_free');
        $shippingCost = \Verba\_mod('order')->gC('shipping');

        if($total - $discount >= $shippingFreeValue){
            $shipping = 0;
        }else{
            $shipping = $shippingCost;
        }

        return $shipping;
    }
}
