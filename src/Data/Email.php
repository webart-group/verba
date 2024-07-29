<?php

namespace Verba\Data;

class Email extends Regexp
{
    public $type = 'email';
    public $format = '^[\w\-]+(\.?[\w\-]+)*@[a-z0-9](?:[\w\-\.])*\.[a-z]+$';
    public $modificators = 'i';

    function setValue($val)
    {
        $val = strtolower($val);
        parent::setValue($val);
    }
}
