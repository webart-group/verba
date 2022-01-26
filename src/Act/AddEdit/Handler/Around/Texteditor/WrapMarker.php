<?php

namespace Verba\Act\AddEdit\Handler\Around\Texteditor;

use \Verba\Act\AddEdit\Handler\Around;

class WrapMarker extends Around
{
    function run(){
        if(!isset($this->value)){
            return null;
        }
        $marker = '+++';
        $matches = array('/(?!<\!--)'.quotemeta($marker).'(?!\-->|\+)/i');
        $replace = array('<!--'.$marker.'-->');

        return preg_replace($matches, $replace, $this->value);
    }
}
