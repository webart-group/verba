<?php

namespace Verba\Mod\Paysys;

use Verba\Mod\Instance;

class Card extends \Verba\Mod
{
    use \Verba\ModInstance;
    use \Verba\Mod\Payment\Paysys;

    /**
     * @param $value string|integer
     * @return string|integer|bool
     */
    function validateAccountValue($value, $curId = false)
    {
        $value = preg_replace("/[\D]/", '', $value);
        if (!preg_match("/[0-9]{16}/i", $value)) {
            return false;
        }
        return $value;
    }
}
