<?php
namespace Verba\Mod\Cart\Actions;

class CheckItemsAvaibility extends \Verba\Block\Html
{

    function build()
    {
        $this->content = \Verba\_mod('cart')->checkItemsAvaibility($this->request->getParam('items'));
        return $this->content;
    }
}
