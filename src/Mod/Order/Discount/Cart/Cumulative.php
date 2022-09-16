<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 16:37
 */

namespace Verba\Mod\Order\Discount\Cart;

class Cumulative extends \Verba\Mod\Order\Discount\Cart{

    function recount(){
        $this->value = 0;
        $this->percent = 0;
        $baseTotal = $this->getOrderTotal();
        $cp = $this->cart->getProfile();

        $cusStatusId = $cp->recountStatusId($baseTotal);

        $d = 0;
        foreach($this->cart->getItems() as $hash => $item){
            $d += $item->getCustomerStatusDiscount($cusStatusId) * $item->getQuantity();
        }
        $d = (float)$d;

        if(!$d){
            return $this->value;
        }
        $this->value = $d;
        $this->percent = !$this->value
            ? 0
            : $this->value * 100 / $baseTotal;

        return $this->value;
    }

}