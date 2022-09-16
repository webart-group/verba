<?php
trait chatik_includes{

  function includeEssentials(){

    Lang::sendToClient('chatik client');

    $this->addScripts(array(
      array('chatik','chatik'),
    ));

    $this->addCss(array(
      array('chatik')
    ));

  }

  function getChatikInstanceTpl(){

    return 'chatik/ui.tpl';

  }

}
?>