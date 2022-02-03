<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 16:37
 */

namespace Mod\Order\Discount\Cart;

class Personal extends \Verba\Mod\Order\Discount\Cart{

    function __construct($cfg, $cart){
        parent::__construct($cfg, $cart);
        $this->_value = $this->cart->getProfile()->getPdiscount();
        if(!$this->_value){
            $this->_value = 0;
        }
        $this->value = $this->_value;
    }

    function isApplicable(){
        $this->applicable = parent::isApplicable();

        if(!$this->applicable){
            return $this->applicable;
        }

        if($this->_value > 0){
            $this->applicable = true;
        }else{
            $this->applicable = false;
        }

        return $this->applicable;
    }
}