<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use Act\AddEdit\Handler\Around;

class StoreId extends Around
{
    function run()
    {
        if($this->action != 'new'){
            return null;
        }

        $orderCreateData = $this->ah->getExtendedData('orderCreateData');
        if(!$orderCreateData || !$orderCreateData instanceof \Verba\Mod\Order\CreateData){
            $this->log()->error('Bad Store id');
            return false;
        }

        if(!is_object($orderCreateData->Store)){
            return false;
        }
        return $orderCreateData->Store->id;
    }
}
