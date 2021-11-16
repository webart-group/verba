<?php

namespace Verba\Act\AddEdit\Handler\Around\OType\Attr;

use Act\AddEdit\Handler\Around;

class Roles extends Around
{
    function run()
    {
        if(!is_string($this->value) || !count($this->value)){
            return null;
        }
        $this->value = trim($this->value);
        $this->value = str_replace(',', ' ', $this->value);
        $ivalues = explode(' ', $this->value);
        $vs = array();
        foreach($ivalues as $cv){
            if(!strlen($cv)){
                continue;
            }
            $vs[] = $cv;
        }
        if(!count($vs)){
            return '';
        }
        return implode(',',$vs);
    }
}
