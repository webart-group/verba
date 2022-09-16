<?php
namespace Verba\Mod\Cart\Item;

class Resource extends \Verba\Mod\Cart\Item
{
    function setPrice($val)
    {

        if (is_array($this->tform['data'])
            && $this->tform['data']['cost']) {
            $val = $this->tform['data']['cost'];
        }

        $this->_props['price'] = (float)$val;
    }
}
