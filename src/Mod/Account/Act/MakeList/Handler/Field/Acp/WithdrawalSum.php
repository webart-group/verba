<?php

namespace Mod\Account\Act\MakeList\Handler\Field\Acp;

use Act\MakeList\Handler\Field;

class WithdrawalSum extends Field{

  function run(){

    $opSign = $this->list->row['taxOut'] <> 0
      ? ($this->list->row['taxOut'] > 0 ? '-' : '+')
      : '' ;
    $symbol =  \Mod\Currency::getInstance()->getCurrency($this->list->row['currencyId'])->symbol;
    return \Verba\reductionToCurrency($this->list->row['sum']) . ' ' . $symbol
      . '<br>'
      . $opSign . \Verba\reductionToCurrency(abs($this->list->row['taxOut'])) . ' ' . $symbol
      . '<br><span>' . \Verba\reductionToCurrency($this->list->row['sumout']) . '</span> ' . $symbol
      ;

  }

}
