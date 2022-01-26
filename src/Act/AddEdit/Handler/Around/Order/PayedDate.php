<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use \Verba\Act\AddEdit\Handler\Around;

class PayedDate extends Around
{
    function run()
    {
        if($this->action != 'edit'){
            return null;
        }

        $exists_value = $this->ah->getExistsValue('payed');
        $new_value = $this->ah->getTempValue('payed');

        if($new_value != 1 || $exists_value == $new_value){
            return null;
        }

        return date('Y-m-d H:i:s');
    }
}
