<?php

namespace Verba\Act\Form\Element\Extension;

use \Verba\Act\Form\Element\Extension;

class AttrHandlerTypeValues extends Extension{

  function engage(){
    $this->fe->listen('loadValuesBefore', 'loadValues', $this);
  }

  function loadValues(){
    $r = \Mod\Otype::getInstance()->getOAttrAhsTypes();
    $this->fe()->setValues($r);
  }
}
