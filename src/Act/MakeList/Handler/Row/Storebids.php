<?php
namespace Verba\Act\MakeList\Handler\Row;

use \Verba\Act\MakeList\Handler\Row;

class Storebids extends Row {

  function run(){

    $activeClass = $this->list->row['active'] ? 'is-active' : 'not-active';

    $this->list->rowClass[] = $activeClass;


    return true;
  }

}
