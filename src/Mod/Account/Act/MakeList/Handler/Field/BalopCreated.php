<?php

namespace Verba\Mod\Account\Act\MakeList\Handler\Field;

use Verba\Act\MakeList\Handler\Field;

class BalopCreated extends Field{

  function run(){

    return '<div>' . \Verba\Mod\Shop::formatDate(strtotime($this->list->row['created'])) . '</div>'
      . '<div>' . $this->list->row['code'].'</div>';

  }

}
