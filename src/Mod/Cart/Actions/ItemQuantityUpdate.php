<?php


namespace Verba\Mod\Cart\Actions;


class ItemQuantityUpdate extends \Verba\Block\Html
{

    function build()
    {
        $this->content = \Verba\_mod('cart')->itemQuantityUpdate($this->request->getParam('item'))->packToClient();
    }

}
