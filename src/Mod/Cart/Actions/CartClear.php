<?php
namespace Verba\Mod\Cart\Actions;

class CartClear extends \Verba\Block\Html
{

    function build()
    {
        $this->content = \Verba\_mod('cart')->resetAndClearItems();
        return $this->content;
    }
}