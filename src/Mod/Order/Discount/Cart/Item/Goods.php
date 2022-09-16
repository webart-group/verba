<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 16:40
 */

namespace Verba\Mod\Order\Discount\Cart\Item;


class Goods extends \Verba\Mod\Order\Discount\Cart\Item{

    protected $applicable = false;

    function recount(){
        $this->value = 0;
        $this->percent = 0;

        if(empty($this->linkedGoods)){
            return $this->value;
        }
        $allGoodsTotal = 0;
        foreach($this->linkedGoods as $item_hash => $CartItem){
            $allGoodsTotal += $CartItem->getPrice() * $CartItem->getQuantity();
        }

        $mpp = $allGoodsTotal / 100;
        if($this->valuetype == 'percent'){
            $this->value = $mpp * $this->_value;
            $this->percent = $this->_value;
        }else{
            $this->value = $this->_value;
            $this->percent = $this->_value / $mpp;
        }
        return $this->value;
    }

}