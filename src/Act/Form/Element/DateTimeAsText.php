<?php

namespace Verba\Act\Form\Element;

use \Html\Element;

class DateTimeAsText extends Element
{
  function makeE(){
    $this->fire('makeE');
    $cnt = \Verba\_mod('order')->handleDeliveryDateAndTime(null, $this->aef->getExistsValues());
    $this->setE($cnt);
    $this->fire('makeEFinalize');
  }
}