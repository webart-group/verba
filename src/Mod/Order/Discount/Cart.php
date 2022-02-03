<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 16:30
 */

namespace Mod\Order\Discount;


class Cart extends \Verba\Mod\Order\Discount{

    function getOrderTotal(){
        return $this->getNoPromosItemsTotal();
    }

    function isApplicable(){
        $this->applicable = true;
        // check if cart have not only 'goods'-affected discounts items
        $items = $this->cart->getItems();
        if(is_array($items) && count($items)){
            $i = 0;
            foreach($items as $hash => $Item){
                $itemGoodsPromos = $Item->getPromosByAffect('goods');
                if(is_array($itemGoodsPromos) && !empty($itemGoodsPromos)){
                    $i++;
                }
            }
            //all cart items have own promotions - not applicable
            if($i == count($items)){
                $this->applicable = false;
            }
        }
        return $this->applicable;
    }

}