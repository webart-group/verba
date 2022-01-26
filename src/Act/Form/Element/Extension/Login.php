<?php

namespace Verba\Act\Form\Element\Extension;

use \Verba\Act\Form\Element\Extension;

class Login extends Extension
{
  function engage(){
    $this->fe->listen('prepare', 'hookScripts', $this);
  }

  function hookScripts(){
    $onChangeScript = array('change' => "loginVerify('{$this->fe()->getId()}')");
    $this->fe()->setEvents($onChangeScript);
  }
}
