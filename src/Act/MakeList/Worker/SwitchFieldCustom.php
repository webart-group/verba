<?php
namespace Verba\Act\MakeList\Worker;

use \Verba\Act\MakeList\Worker;

class SwitchFieldCustom extends Worker{

  public $field = '';
  protected $template = '';

  function init(){
    $this->parent->listen('beforeParse', 'run', $this);
  }

  function run(){
    $this->parent->addClientTemplate('lwk-switch-field-custom'.(!empty($this->field) ? '-'.$this->field : ''), $this->template);
  }

}
