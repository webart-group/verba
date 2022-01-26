<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class FilekeyStatusUpdate extends Around
{
    function run()
    {
        if($this->action != 'edit'){
            return $this->value;
        }

        $existsValue = $this->getExistsValue('state');
        if($this->value == $existsValue){
            return $this->value;
        }

        // Unlink with possibl order
        $_fk = \Verba\_oh('filekey');
        if($this->value == 120){
            $_order = \Verba\_oh('order');
            $oot = $_order->getID();
            $br = \Verba\Branch::get_branch(array(
                    $_fk->getID() => array('aot'=>$oot, 'iids'=> $this->ah->getIID()))
                , 'up'
            );
            if(isset($br['handled'][$oot])
                && count($br['handled'][$oot])){
                $this->ah->addToUnlink($oot, $br['handled'][$oot]);
            }
        }

        return $this->value;
    }
}
