<?php
namespace Verba\Act\AddEdit\Handler\After;

use \Verba\Act\AddEdit\Handler\After;

class UserReloadRequire extends After{

  //protected $allowed = array('edit');
    protected $_allowedNew = false;

  function run(){
    $U = \Verba\User();
    if(!$this->ah->isUpdated()
    || $this->ah->getIID() != $U->getID()){
      return;
    }

    $U->planeToReload();
  }
}
