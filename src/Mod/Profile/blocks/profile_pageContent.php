<?php
class profile_pageContent extends page_content{
  /**
   * @var \Verba\Mod\User\Model\User
   */
  protected $U;
  protected $userId;

  public $coloredPanelCfg = array();
  public $titleLangKey = 'profile titles common';

  public $bodyClass = 'profile-page';


  function init(){

    if(!$this->U){
      $this->U = \Verba\User();
    }
    if(!$this->U || !$this->U instanceof \Verba\Mod\User\Model\User
      || $this->U->getID() !=\Verba\User()->getID())
    {
      throw  new \Verba\Exception\Building('Unknown user');
    }

    $this->userId = $this->U->getID();

    if(!$this->userId){
      throw  new \Verba\Exception\Building('Unknown user');
    }
  }

  function getU(){
    return $this->U;
  }

}
?>
