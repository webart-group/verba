<?php
namespace Verba\Mod\Cart\Block;

class CartPageBlock extends \Verba\Block\Html
{
    public $role = 'cart-widget';

    function build()
    {
        $this->setScripts([
            ['cartView cartView_block orderDiscountView', 'shop'],
        ]);

        $this->tpl->define([
            'content' => 'shop/cart/placeholder.tpl'
        ]);
        $pad = \Verba\Lang::get('cart case');
        $root = \Verba\Lang::get('cart totalQuant');

        $funcName = '\Verba\make_padej_' . \Verba\Lang::$locale;

        for ($i = 0; $i < 10; ++$i) {
            $padezh[$i] = call_user_func($funcName, $i, $root, $pad);
        }

        $cfg = array(
            'container' => '#cart-holder',
            'minOrderCost' => \Verba\_mod('order')->gC('minimal_cost'),
            'case' => $padezh
        );

        $this->tpl->assign(array('CART_CFG' => json_encode($cfg)));

        $this->content = $this->tpl->parse(false, 'content');

        return $this->content;
    }
}
