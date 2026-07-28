<?php


namespace Verba\Mod\Cart\Actions;


class ItemDelete extends \Verba\Block\Html
{

    function build()
    {
        $this->content = \Verba\_mod('cart')->itemDelete($this->request->getParam('hash'));
        return $this->content;
    }
}
