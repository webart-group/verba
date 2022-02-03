<?php

namespace Mod\Account\Act\MakeList\Handler\Field\Acp;

use Act\MakeList\Handler\Field;

class WithdrawalPreq extends Field{

  function run(){



    return $this->list->row['account']
      . '<br>' . $this->list->row['currencyId__value'].', '.$this->list->row['paysysId__value']
      ;

  }

}
