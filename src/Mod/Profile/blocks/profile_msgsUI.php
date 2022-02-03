<?php
class profile_msgsUI extends chatik_hubUI {

  public $notifierCfg = 'user';
  public $titleLangKey = 'profile msgs pageTitle';

  function route()
  {
    $cb = new page_contentTitled($this, array(
      'items' => array(
        'CONTENT' => $this,
      )
    ));
    return $cb;
  }

  function init(){

    if(!User()->getAuthorized()){
      throw  new \Verba\Exception\Building('Bad reqqqquest');
    }

    $this->forOt = \Verba\_oh('user')->getID();
    $this->forId =\Verba\User()->getId();
  }

}
?>