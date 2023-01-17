<?php

namespace Verba\Mod\Account\Act\MakeList\Handler\Row;

use Verba\Act\MakeList\Handler\Row;

class Withdrawal extends Row {

  function run(){

    switch($this->list->row['status']){
      case '180':
        $statusClass = 'in-progress';
        break;
      case '181':
        $statusClass = 'complete';
        break;
      case '182':
        $statusClass = 'canceled';
        break;
      default:
        $statusClass = 'unknown';
        break;
    }
    $this->list->rowClass[] = 'wdl-status-'.$statusClass;

    return true;
  }

}
