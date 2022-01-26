<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class TaxPaysystem extends Around
{
    function run()
    {
        if($this->action == 'edit'
            && $this->ah->getTempValue('topay') === null
            || $this->ah->getTempValue('topay') == $this->getExistsValue('topay'))
        {
            return $this->value;
        }
        $paysys = \Verba\_mod('payment')->getPaysys($this->ah->getTempValue('paysysId'));
        $topay = $this->ah->getTempValue('topay');
        if(!$paysys->tax_input || !$topay){
            return 0;
        }

        $this->value = $topay * ($paysys->tax_input/100);
        return $this->value;
    }
}
