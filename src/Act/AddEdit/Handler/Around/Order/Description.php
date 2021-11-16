<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use Act\AddEdit\Handler\Around;

class Description extends Around
{
    function run()
    {
        $items = $this->ah->getExtendedData('items');
        if(!$items && !is_array($items)){
            return false;
        }

        // Получение первого товара в заказе для формирования описания заказа
        foreach($items as $hash => $cartItem) {
            if(!$cartItem instanceof \Verba\Mod\Cart\Item){
                continue;
            }
            break;
            /*(
              $item->getQuantity() > 1 ? ' x '.$item->getQuantity() : ''
            );*/
        }

        if(!isset($cartItem) || !$cartItem instanceof \Verba\Mod\Cart\Item) {
            return null;
        }

        $desc = $cartItem->description;
        return is_string($desc) && !empty($desc) ? $desc : null;
    }
}
