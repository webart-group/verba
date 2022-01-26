<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class UserTrustCreate extends Around
{
    function run()
    {
        if($this->action != 'new' || isset($this->value)){
            return $this->value;
        }

        $_ut = \Verba\_oh('usertrust');
        $q = "SELECT `id` FROM ".$_ut->vltURI()." WHERE `active` = 1 ORDER BY `priority` ASC LIMIT 1";
        $sqlr = $this->DB()->query($q);
        if(!$sqlr || !$sqlr->getNumRows()){
            return 0;
        }
        $this->value = $sqlr->getFirstValue();

        return $this->value;
    }
}
