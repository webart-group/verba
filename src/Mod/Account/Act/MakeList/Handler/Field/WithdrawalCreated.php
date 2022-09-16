<?php

namespace Verba\Mod\Account\Act\MakeList\Handler\Field;

use Act\MakeList\Handler\Field;

class WithdrawalCreated extends Field{

  function run(){

    return \Verba\Mod\Shop::formatDate(strtotime($this->list->row['created']))
      .'<br>'
      .'<span>'.$this->list->row['balopCode'].'</span>';

  }
}
