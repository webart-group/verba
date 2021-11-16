<?php

namespace Verba\Act\Form\Element\Extension;

use Act\Form\Element\Extension;


class Unique extends Extension
{
  function engage(){
    $this->fe->listen('prepare', 'hookScripts', $this);
  }

  function hookScripts(){
    $onChangeScript = array();
    $this->fe()->setEvents($onChangeScript);
  }
}
