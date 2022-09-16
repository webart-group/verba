<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 16:26
 */

namespace Verba\Mod\Order;


class Discount extends \Verba\Configurable{
    public $id;
    public $type;
    public $title;
    public $value = 0;
    public $_value = 0;
    public $percent = 0;
    public $valuetype = 'percent'; // percent, money
    public $context;
    public $affect;
    public $cart;
    protected $applicable = true;

    function __construct($cfg = false, $cart){
        if(!$cart || !$cart instanceof \Verba\Mod\Cart\CartInstance){
            throw new \Exception('\Mod\Order\Discount: Invalid Cart object');
        }
        $this->cart = $cart;
        if(is_array($cfg)){
            $this->applyConfigDirect($cfg);
        }
        $this->_value = $this->value;
    }

    function refresh(){

    }

    function recount(){
        $this->value = 0;
        $this->percent = 0;

        if(!$this->applicable){
            return $this->value;
        }
        $baseTotal = $this->getOrderTotal();
        $mpp = $baseTotal / 100;

        if($this->valuetype == 'percent'){
            $this->value = $mpp * $this->_value;
            $this->percent = $this->_value;
        }else{
            $this->value = $this->_value;
            $this->percent = $this->_value / $mpp;
        }

        return $this->value;
    }

    function applyTo($price){
        $price = (int)$price;
        if(!$price || $price < 0){
            return $price;
        }

        if($this->valuetype == 'percent'){
            $r = $price - ($price / 100 * $this->_value);
        }else{
            $r = $price - $this->_value;
        }
        return $r;
    }

    function isApplicable(){
        return $this->applicable;
    }

    function getNoPromosItemsTotal(){
        $items = $this->cart->getItems();
        $total = 0;
        foreach($items as $hash => $Item){
            /**
             * @var $Item \Verba\Mod\Cart\Item
             */
            $goodsPromos = $Item->getPromosByAffect('goods');
            if(is_array($goodsPromos) && !empty($goodsPromos)){
                continue;
            }
            $total += $Item->getPrice() *$Item->getQuantity();
        }
        return $total;
    }

    function packToStore(){
        return array(
            '_class' => get_class($this),
            'title' => $this->title,
            'value' => $this->value,
            '_value' => $this->_value,
            'valuetype' => $this->valuetype,
            'type' => $this->type,
            'affect' => $this->affect,
            'context' => $this->context,
            'percent' => $this->percent,
        );
    }

    function packToCart(){
        return $this->packToStore();
    }

    function getOrderTotal(){
        return $this->cart->getTotal();
    }

}
