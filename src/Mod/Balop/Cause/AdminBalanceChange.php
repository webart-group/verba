<?php

namespace Mod\Balop\Cause;

use Mod\Balop\Cause;

class AdminBalanceChange extends Cause{

    protected $block;

    function setSum($val)
    {
        $this->sum = \Verba\reductionToCurrency($val);
        return $this->sum;
    }

    function setBlock($val)
    {
        $this->block = (int)((bool)$val);
        return $this->block;
    }

    function validate(){
        $this->_valid = false;

        $mAcp = \Verba\_mod('acp');
        if(!$mAcp->checkAccess()){
            $this->log()->secure('Attemt to change balance as Admin');
            return $this->_valid;
        }

        $this->_valid = true;
        return $this->_valid;
    }
}