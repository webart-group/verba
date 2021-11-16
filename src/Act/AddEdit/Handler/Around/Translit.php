<?php

namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class Translit extends Around
{
    function run()
    {
        if(!empty($this->value)) return $this->value;
        if(!isset($this->params['secondary'])
            || !is_object($Av = $this->oh->A($this->params['secondary']))
            || ($this->ah->getTempValue($Av->getCode()) === null)
        ){
            return false;
        }
        if($Av->isLcd()){
            if(isset($this->params['lang']) && is_string($this->params['lang']) && !empty($this->params['lang'])){
                $lang = $this->params['lang'];
            }else{
                $lang = \Verba\Lang::getDefaultLC();
            }
            $this->value = $this->ah->getTempValue($Av->getCode())[$lang];
        }else{
            $this->value = $this->ah->getTempValue($Av->getCode());
        }

        if(!is_string($this->value)){
            return null;
        }

        return \Verba\translit($this->value, array(), $lang);
    }
}
