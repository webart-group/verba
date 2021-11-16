<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use Act\AddEdit\Handler\Around;

class QuantityAvaibleUpdate extends Around
{
    function run()
    {
        if(!preg_match("/^([\+\-]?)(.+)$/i", $this->value, $matches)){
            return null;
        }
        $val = reductionToFloat($matches[2]);

        $existsValue = $this->getExistsValue($this->A->getCode());
        if($matches[1] == '-'){
            $newVal = $existsValue - $val;
        }elseif($matches[1] == '+'){
            $newVal = $existsValue + $val;
        }else{
            $newVal = $val;
        }

        return $newVal;
    }
}
