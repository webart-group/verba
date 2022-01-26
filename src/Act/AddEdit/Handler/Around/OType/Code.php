<?php

namespace Verba\Act\AddEdit\Handler\Around\OType;

use \Verba\Act\AddEdit\Handler\Around;

class Code extends Around
{
    function run()
    {
        if($this->action == 'edit'){
            $evalue = $this->getExistsValue('ot_code');
        }else{
            $evalue = null;
        }
        $this->value = strtolower((string)$this->value);
        if(!$this->value || $this->value === $evalue){
            return null;
        }
        $this->value = preg_replace("/[^a-z0-9_]/i", '_', $this->value);

        if($this->action == 'new' && $this->ah->getTempValue('base')){
            $_base = \Verba\_oh($this->ah->getTempValue('base'));
            $base_code = $_base->getCode();
            if(strpos($this->value, $base_code) !== 0){
                $this->value = $base_code .'_'.$this->value;
            }
        }

        \Verba\_mod('system')->planeClearCache();
        return $this->value;
    }

}
