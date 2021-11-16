<?php

namespace Verba\Data;


class Boolean extends \Verba\Data
{
    public $type = 'bool';

    function validate()
    {
        return;
    }

    static function getValues()
    {
        return array(
            '0' => \Verba\Lang::get('TFormaters bool values 0'),
            '1' => \Verba\Lang::get('TFormaters bool values 1'),
        );
    }

    /**
     * from http://php.net/is_bool @Michael Smith
     */
    static function toBool($var)
    {
        if (!is_string($var)) return (bool)$var;
        switch (strtolower($var)) {
            case '1':
            case 'true':
            case 'on':
            case 'yes':
            case 'y':
                return true;
            case '0':
            case 'false':
            case 'off':
            case 'no':
            case 'n':
                return false;
            default:
                return null;
        }
    }

    static function isStrBool($val)
    {
        if (!is_string($val)) {
            return false;
        }
        $val = strtolower(trim($val));
        return $val == 'false' || $val == 'true';
    }
}