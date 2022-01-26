<?php

namespace Verba\Act\AddEdit\Handler\Around\Gamebid\Resource;

use \Verba\Act\AddEdit\Handler\Around;

class DiscountCollector extends Around
{
    function run()
    {
        if($this->action != 'new'){
            return $this->value;
        };

        $this->value = 0;

        $this->ah->setGettedData(
            [
                'discount' => $this->value,
                'discountDetails' => ''
            ]
        );

        return $this->value;
    }
}
