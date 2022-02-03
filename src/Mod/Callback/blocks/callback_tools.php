<?php
class callback_tools extends \Verba\Block\Html{

  function prepare(){
    $this->addScripts(array(
      array('callback','callback'),
      array('phone','form/e'),
    ));
    $this->addCSS(array(
      array('form callback'),
      array('fh-phone', 'form'),
    ));
  }
}
?>