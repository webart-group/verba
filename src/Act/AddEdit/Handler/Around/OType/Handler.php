<?php

namespace Verba\Act\AddEdit\Handler\Around\OType;

use \Verba\Act\AddEdit\Handler\Around;

class Handler extends Around
{
    function run(){
        $this->value = (string)$this->value;
        if($this->value){
            return $this->value;
        }

        if($this->ah->getTempValue('base')){
            $handler = \Verba\_oh($this->ah->getTempValue('base'))->getHandler();
        }

        if(!isset($handler) && $this->action == 'new'){
            $handler = '\Model';
        }
        return isset($handler) ? $handler : null;
    }
}
