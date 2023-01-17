<?php

namespace Verba\Mod\Account\Act\MakeList\Handler\Field;

use Verba\Act\MakeList\Handler\Field;

class WithdrawalSum extends Field{

  function run(){

    $opSign = $this->list->row['taxOut'] <> 0
      ? ($this->list->row['taxOut'] > 0 ? '-' : '+')
      : '' ;

    return \Verba\Mod\Shop::formatSum($this->list->row['sum'], $this->list->row['currencyId'])
      . '<br>'
      . $opSign . \Verba\Mod\Shop::formatSum(abs($this->list->row['taxOut']), $this->list->row['currencyId']);

  }

}
