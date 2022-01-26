<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use \Verba\Act\AddEdit\Handler\Around;

class DiscountDetails extends Around
{
    function run()
    {
        if($this->action != 'new'){
            return $this->value;
        }
        $Cart = $this->ah->getExtendedData('cart');
        $r = array();
        foreach($Cart->getPromos() as $did => $item){
            if(!$item instanceof \Verba\Mod\Order\Discount){
                continue;
            }
            $r[$did] = $item->packToStore();
        }

        return !count($r) ? '' : json_encode($r);
    }
}
