<?php

namespace Verba\Mod\Cart\Actions;


class ItemAdd extends \Verba\Block\Html
{
    function build()
    {
        $this->content = \Verba\_mod('cart')->addItem($this->request->getParam('item'))->packToClient();
        return $this->content;
    }
}
