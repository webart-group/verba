<?php

namespace Verba\Act\AddEdit\Handler\Around\Gamebid\Resource;

use Act\AddEdit\Handler\Around;

class Amount extends Around
{
    function run()
    {
        if($this->action != 'new'){
            return $this->value;
        }
        $this->value = ceil($this->value);
        $prodItem = $this->ah->getExtendedData('prodItem');
        $err = 0;
        $min = (int)$prodItem->getValue('amountMin');
        if($min && $this->value < $min ){
            $this->log()->error('Out of limits. Minimum amount is '.$min.', given: '.var_export($this->value, true));
            $err++;
        }
        $max = (int)$prodItem->getValue('quantityAvaible');
        if($max && $this->value > $max){
            $this->log()->error('Out of limits. Maximum amount is '.$max.', given: '.var_export($this->value, true));
            $err++;
        }

        if($err){
            return false;
        }

        return $this->value;
    }
}
