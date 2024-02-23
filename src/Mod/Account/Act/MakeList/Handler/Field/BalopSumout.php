<?php

namespace Verba\Mod\Account\Act\MakeList\Handler\Field;

use Verba\Act\MakeList\Handler\Field;

class BalopSumout extends Field{

  function run(){

    return \Verba\Mod\Shop::formatSum($this->list->row['sumout'], $this->list->row['accCurrencyId']);

  }

}
