<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 16:30
 */

namespace Mod\Order\Discount\Cart;


class Item extends \Verba\Mod\Order\Discount{

    protected $cartItem;
    protected $linkedGoods = array();

    function setCartItem($cartItem){
        if(!($cartItem instanceof \Mod\Cart\Item)){
            return false;
        }
        $this->linkedGoods = array($cartItem->getHash() => $cartItem);
        return current($this->linkedGoods);
    }
    function addCartItem($cartItem){
        if(!($cartItem instanceof \Mod\Cart\Item)){
            return false;
        }
        $this->linkedGoods[$cartItem->getHash()]  = $cartItem;
        return $this->linkedGoods[$cartItem->getHash()];
    }
    function getCartItem($hash = false){
        if(!is_string($hash)){
            $hash = count($this->linkedGoods)
                ? key($this->linkedGoods)
                : false;
        }

        if(!count($this->linkedGoods)
            || !array_key_exists($hash, $this->linkedGoods)){
            return null;
        }
        return $this->linkedGoods[$hash];
    }

    function isApplicable(){
        if(empty($this->linkedGoods)){
            $this->applicable = false;
        }else{
            foreach($this->linkedGoods as $hash => $Item){
                $isItm = $this->cart->getItem($hash);
                if(!$isItm || $isItm != $Item){
                    unset($this->linkedGoods[$hash]);
                }
            }
            $this->applicable = empty($this->linkedGoods) ? false : true;
        }
        return $this->applicable;
    }
}
