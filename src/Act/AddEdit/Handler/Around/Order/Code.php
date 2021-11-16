<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use Act\AddEdit\Handler\Around;

class Code extends Around
{
    function run()
    {
        if($this->action != 'new'){
            return null;
        }

        $q = "SELECT COUNT(*) as `c` FROM ".$this->oh->vltURI()." WHERE `created` >= '".date('Y-m-d 00:00:00', time())."'";
        $sqlr = $this->DB()->query($q);
        if(!$sqlr){
            $this->log()->error('Unable to obtain exists orders count');
            return false;
        }
        $ordersTodayCount = (int)$sqlr->getFirstValue();
        if(!$ordersTodayCount){
            $ordersTodayCount = 1;
        }
        $iid = (string)$this->ah->getIID();
        $r = array();
        for($i=0; $i < strlen($iid); $i++){
            $r[$i] = $iid[$i];
        }
        $fp = array_slice($r, 0,2);
        $sp = array_slice($r, 2);

        $fp = implode('',$fp);
        $sp = implode('',$sp);
        $alpha_range = range('a','z');
        $start_year = 2017;
        $cyear = (int)date('Y');
        $alpha = $alpha_range[$cyear - $start_year];

        $code =
            $alpha
            . $fp
            . rand(1000,9999)
            . $sp
            . $ordersTodayCount
        ;
        return strtoupper($code);
    }
}