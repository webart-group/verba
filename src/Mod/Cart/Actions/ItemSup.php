<?php
namespace Verba\Mod\Cart\Actions;

class ItemSup extends \Verba\Block
{
    function prepare()
    {
        \Verba\_mod('cart')->clearSessionCache();
    }
}
