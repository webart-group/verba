<?php

namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class StrToSeo extends Around
{
    function run()
    {
        if(!empty($this->value)) {
            return $this->value;
        }
        if(isset($this->params['secondary'])){
            $Av = $this->oh->A($this->params['secondary']);
        }elseif($this->oh->isA('title')){
            $Av = $this->oh->A('title');
        }

        if(!isset($Av) || !is_object($Av)
            || ($this->ah->getTempValue($Av->getCode()) === null))
        {
            return false;
        }
        $this->value = $this->ah->getTempValue($Av->getCode());
        if($Av->isLcd())
        {
            if(is_array($this->value)){
                if(isset($this->value[$this->lc])) {
                    $this->value = $this->value[$this->lc];
                }elseif(count($this->value)) {
                    $this->value = current($this->value);
                }
            }
        }

        if(!is_string($this->value)){
            return null;
        }

        return \Verba\strConvertToSeo($this->value);
    }
}
