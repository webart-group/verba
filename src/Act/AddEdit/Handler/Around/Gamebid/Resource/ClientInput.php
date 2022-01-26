<?php

namespace Verba\Act\AddEdit\Handler\Around\Gamebid\Resource;

use \Verba\Act\AddEdit\Handler\Around;

class ClientInput extends Around
{
    function run(){
        if($this->action != 'new'){
            return $this->value;
        }
        $this->value = array(
            'amount' => $this->ah->getGettedValue('amount'),
            'cost' => $this->ah->getGettedValue('cost'),
            'topay' => $this->ah->getGettedValue('topay'),
        );
        return json_encode($this->value);
    }
}
