<?php

namespace Mod\Account\Act\MakeList\Handler\Field;

use Act\MakeList\Handler\Field;

class WithdrawalSum extends Field{

  function run(){

    $opSign = $this->list->row['taxOut'] <> 0
      ? ($this->list->row['taxOut'] > 0 ? '-' : '+')
      : '' ;

    return \Mod\Shop::formatSum($this->list->row['sum'], $this->list->row['currencyId'])
      . '<br>'
      . $opSign . \Mod\Shop::formatSum(abs($this->list->row['taxOut']), $this->list->row['currencyId']);

  }

}
