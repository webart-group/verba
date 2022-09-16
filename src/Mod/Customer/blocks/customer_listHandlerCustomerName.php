<?php

namespace Verba\Mod\Customer\Act\MakeList\Handler\Field;

class Name extends \Act\MakeList\Handler{

  function handle(){
    return \User::getFullName($this->list->row);
  }

}
