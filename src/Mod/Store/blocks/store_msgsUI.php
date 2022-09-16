<?php
class store_msgsUI extends chatik_hubUI {

  /**
   * @var $Store \Model\Store
   */
  public $Store;

  public $notifierCfg = 'store';
  public $titleLangKey = 'store smsgs pageTitle';

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
      throw  new \Verba\Exception\Building('Bad request');
    }


    $this->forOt = \Verba\_oh('store')->getID();
    $this->forId = $this->Store->getId();


  }

  function generateNotifierCfg(){
    /**
     * @var $mChat Chatik
     */
    $mChat = \Verba\_mod('Chatik');
    $this->notifierCfg = $mChat->genNotifierCfgFor($this->notifierCfg, $this->Store);
    $this->notifierCfg['priority'] = 1000;
  }

}
?>

