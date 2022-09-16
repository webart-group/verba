<?php

namespace Verba\Act\AddEdit\Handler\Around\Texteditor;

use \Verba\Act\AddEdit\Handler\Around;

class TextPreview extends Around
{
    function run()
    {
        if(!isset($this->params['secondary'])
            || !is_object($Av = $this->oh->A($this->params['secondary']))
            || !isset($this->tempData[$Av->getCode()])
        ){
            return $this->value;
        }

        $length = is_numeric($this->params['length']) && $this->params['length'] > 0
            ? $this->params['length']
            : 300;
        $strip_to = !empty($this->params['strip_to'])
            ? $this->params['strip_to']
            : false;

        if ($this->params['strip_tags']) {
            $this->value = strip_tags($this->value);
            if(!empty($strip_to) && $length <= strlen($this->value)){
                $this->value = html_entity_decode($this->value, ENT_QUOTES, 'utf-8');
                $pos = false;
                if($length <= strlen($this->value)){
                    $pos = strpos($this->value, $strip_to, $length);
                }
                $pos = $pos !== false ? ($pos+1) - $length : 0;
            }
            $this->value = html_entity_decode(substr($this->value, 0, $length + $pos), ENT_QUOTES, 'utf-8');
        }else{
            $this->value = \HTMLGetFormattedText($this->value, $length);
        }

        return trim($this->value);
    }
}
