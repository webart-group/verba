<?php
//unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Filename extends Around
{
    function run()
    {
        if (!empty($cValue)) return $cValue;
        if (!isset($set_data['params']['secondary'])
            || !is_object($Av = $this->oh->A($this->params['secondary']))
        ) {
            return false;
        }

        $ext = $this->ah->getExtendedData($Av->getCode());

        $cValue = is_array($ext) && array_key_exists('name', $ext) && $ext['name']
            ? $ext['name']
            : null;

        return $cValue;
    }
}
