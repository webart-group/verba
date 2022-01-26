<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class ClearOthersTiedVariants extends Around
{
    function run()
    {

        if(is_string(!$this->set_data['cfg']) || $this->value === null){
            return $this->value;
        }
        $cfg = \Verba\Hive::explodeHandlerParamAsArray($this->set_data['cfg']);
        foreach($cfg as $varId => $tiedAttrCode){
            if($this->value == $varId){
                continue;
            }

            $this->ah->setGettedData([$tiedAttrCode => false]);
        }
        return $this->value;
    }
}
