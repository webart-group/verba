<?php

namespace Verba\Act\Form\Element\OType;

use \Verba\Html\Element;

class Widget extends Element
{
  function makeE(){
    $this->fire('makeE');
    $oh = $this->aef()->oh;
    $jsCfg = array(
      'ot_id' => $oh->getID(),
      'E' => array(
        'wrap' => '#'.$this->aef()->getFormId(),
      ),
      'iid' => $this->aef()->getAction() == 'edit' ? $this->aef()->getIID() : false,
    );
    $this->aef()->tpl()->assign('OTYPE_CLIENT_CFG', json_encode($jsCfg));
    $this->setE('');
    $this->fire('makeEFinalize');
  }
}
