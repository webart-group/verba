<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 16:34
 */

namespace Mod\Order\Discount\Cart;


class FirstPurchase extends \Verba\Mod\Order\Discount\Cart{

    function isApplicable(){

        $this->applicable = parent::isApplicable();

        if(!$this->applicable){
            return $this->applicable;
        }

        if($this->cart->getProfile()->getTotalPurchases() > 0){
            $this->applicable = false;
        }else{
            $this->applicable = true;
        }

        return $this->applicable;
    }

    function recount(){
        $this->value = 0;
        $this->percent = 0;
        $total = $this->getOrderTotal();

        $mpp = $total / 100;
        if(!$this->cart->getProfile()->getTotalPurchases()){
            if($this->valuetype == 'percent'){
                $this->value = $mpp * $this->_value;
                $this->percent = $this->_value;
            }else{
                $this->value = $this->_value;
                $this->percent = $this->_value / $mpp;
            }
        }
        return $this->value;
    }
}