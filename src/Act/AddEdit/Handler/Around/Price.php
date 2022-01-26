<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Price extends Around
{
    function run()
    {
        if($this->value === null){
            return null;
        }
        if(!preg_match("/(\d*)([.|,]*)(\d*)/i", $this->value, $matches)){
            return false;
        }

        $r = (!empty($matches[1]) ? $matches[1] : '0');
        if(!empty($matches[3])){
            $r .= '.' . $matches[3];
        }
        $this->value = $r;

        return $this->value;
    }
}
