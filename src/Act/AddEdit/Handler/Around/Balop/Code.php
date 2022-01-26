<?php

namespace Verba\Act\AddEdit\Handler\Around\Balop;

use \Verba\Act\AddEdit\Handler\Around;

class Code extends Around
{
    function run()
    {

        if($this->action != 'new'){
            return null;
        }

        $alpha_range = range('a','z');
        $start_year = 2018;
        $cyear = (int)date('Y');
        $rk = array_rand($alpha_range, 2);
        $rk_suf = array_rand($alpha_range, 3);
        $prefix = $alpha_range[$cyear - $start_year].$alpha_range[$rk[0]].$alpha_range[$rk[1]];
        $suff = $alpha_range[$rk_suf[0]].$alpha_range[$rk_suf[1]].$alpha_range[$rk_suf[2]];

        $code =
            $prefix
            . rand(10000000,99999999)
            . $suff
        ;
        return strtoupper($code);
    }
}
