<?php

namespace Mod\Account\Act\MakeList\Handler\Field;

use Act\MakeList\Handler\Field;

class BalopSumout extends Field{

  function run(){

    return \Mod\Shop::formatSum($this->list->row['sumout'], $this->list->row['accCurrencyId']);

  }

}
