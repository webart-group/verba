<?php

namespace Mod\Account\Act\MakeList\Handler\Field;

use Act\MakeList\Handler\Field;

class BalopCreated extends Field{

  function run(){

    return '<div>' . \Mod\Shop::formatDate(strtotime($this->list->row['created'])) . '</div>'
      . '<div>' . $this->list->row['code'].'</div>';

  }

}
