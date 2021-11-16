<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use Act\AddEdit\Handler\Around;

class ParentQuantityIncrease extends Around
{
    function run()
    {
        $existsValue = $this->getExistsValue('state');
        if($existsValue == $this->value){
            return $this->value;
        }
        $pot = $this->ah->getFirstParentOt();
        if(!$pot){
            return $this->value;
        }
        $_parent = \Verba\_oh($pot);
        $piid = $this->ah->getFirstParentIid();
        $ae = $_parent->initAddEdit(array('action' => 'edit'));
        $ae->setIID($piid);
        switch(true){
            case ($this->value == 120):
                $val = '+1';
                break;
            case ($existsValue == 120):
                $val = '-1';
                break;
            default:
                return $this->value;
        }

        $ae->setGettedObjectData(array('quantity_avaible' => $val));
        $ae->addedit_object();

        return $this->value;
    }
}
