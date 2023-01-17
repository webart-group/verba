<?php

namespace Verba\Mod\Account\Act\MakeList\Handler\Field;

use Verba\Act\MakeList\Handler\Field;

class WithdrawalPrequisite extends Field{

  function run(){
    $Cur =  \Verba\Mod\Currency::getInstance()->getCurrency($this->list->row['currencyId']);
    return $this->list->row['prequisiteId__value']
      . '<div>'
      . ($Cur instanceof \Verba\Model\Currency ? strtoupper($Cur->code) : '??')
      . ', '. $this->list->row['paysysId__value'] . '</div>';

  }

}
