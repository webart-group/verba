<?php

namespace Verba\Mod\Account\Act\MakeList\Handler\Field;

use Verba\Act\MakeList\Handler\Field;

class WithdrawalStatus extends Field {

  function run(){
    return '<div class="withdrawal-status">'.$this->list->row['status__value'].'</div>';
  }

}
