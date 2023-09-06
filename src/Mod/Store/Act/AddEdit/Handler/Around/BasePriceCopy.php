<?php

namespace Verba\Mod\Store\Act\AddEdit\Handler\Around;

use Verba\Act\AddEdit\Handler\Around;

class BasePriceCopy extends Around
{
    function run()
    {
        $price = $this->ah->getTempValue('price');
        if ($price === null) {
            return null;
        }

        $this->value = $price;

        return $this->value;
    }
}
