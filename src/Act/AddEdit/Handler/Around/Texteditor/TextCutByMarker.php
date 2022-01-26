<?php

namespace Verba\Act\AddEdit\Handler\Around\Texteditor;

use \Verba\Act\AddEdit\Handler\Around;

class TextCutByMarker extends Around
{
    function run()
    {
        if(!isset($this->params['secondary'])
            || !is_object($Av = $this->oh->A($this->params['secondary']))
            || !($this->ah->getTempValue($Av->getCode()) === null)
        ){
            return false;
        }
        $marker = '+++';
        $markerWrapped = '<!--'.$marker.'-->';

        $this->value = $Av->isLcd()
            ? $this->ah->getTempValue($Av->getCode())[$this->lc]
            : $this->ah->getTempValue($Av->getCode());
        if(!is_string($this->value)){
            return null;
        }
        if(($marker_position = mb_strpos($this->value, $markerWrapped)) === false){
            return false;
        }
        $this->value = close_dangling_tags(mb_substr($this->value, 0, $marker_position));
        return $this->value;
    }
}
