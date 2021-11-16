<?php
namespace Verba\Act\MakeList\Worker;

use \Verba\Act\MakeList\Worker;

class ClickAndEdit extends Worker{
  function init(){
    $this->parent->listen('rowBefore', 'run', $this, 'ClickAndEdit');
    $this->parent->sC('edit', 'itemClickAction');
  }
  function run(){
    $this->parent->rowClass[] = 'clickable';
  }
}
?>