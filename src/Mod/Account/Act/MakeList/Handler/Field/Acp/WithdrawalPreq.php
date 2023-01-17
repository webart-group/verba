<?php

namespace Verba\Mod\Account\Act\MakeList\Handler\Field\Acp;

use Verba\Act\MakeList\Handler\Field;

class WithdrawalPreq extends Field{

  function run(){



    return $this->list->row['account']
      . '<br>' . $this->list->row['currencyId__value'].', '.$this->list->row['paysysId__value']
      ;

  }

}
