<?php

namespace Verba\Act\AddEdit\Handler\Around\Texteditor;

use \Verba\Act\AddEdit\Handler\Around;

class TextCutByMarker extends Around
{
    function run()
    {
        if(!is_string($this->value)){
            return $this->value;
        }

        if(!isset($this->params['secondary'])
            || !is_object($Av = $this->oh->A($this->params['secondary']))
            || !($this->ah->getTempValue($Av->getCode()) === null)
        ){
            return $this->value;
        }
        $marker = '+++';
        $markerWrapped = '<!--'.$marker.'-->';

        if(($marker_position = strpos($this->value, $markerWrapped)) !== false){
            $this->value = \Verba\close_dangling_tags(\substr($this->value, 0, $marker_position));
        }

        return $this->value;
    }
}
