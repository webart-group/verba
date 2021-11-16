<?php
namespace Verba\User\RequestHandler;

class PasswordResetForm extends \Verba\Block\Json{

  public $userId;
  public $userData;
  public $code;

  function build(){
    $this->content = '';

    try {

      $code = $this->rq->getParam('code');
      $code = is_string($code)
        ? strtoupper(trim($code))
        : false;

      $email = $this->rq->getParam('email');
      $email = is_string($email)
        ? trim($email)
        : false;

      if (!$email || !$code) {
        throw  new \Verba\Exception\Building(Lang::get('error bad_data'));
      }
      $_user = \Verba\_oh('user');
      //$mUser = \Verba\_mod('User');
      $QM = new \Verba\QueryMaker($_user, false, array('last_password_reset_time', 'email', 'password_reset_code'));
      $QM->addWhere($email, 'email');
      $QM->addWhere($code, 'password_reset_code');
      $QM->addWhere(1, 'active');
      $sqlr = $QM->run();
      if (!$sqlr || $sqlr->getNumRows() != 1) {
        throw  new \Verba\Exception\Building(Lang::get('user reclaim_pass hash_invalid'));
      }

      $this->userData = $sqlr->fetchRow();
      $this->userId = $this->userData[$_user->getPAC()];
      $this->code = $code;
    }catch(Exception $e){
      $this->failed($e->getMessage());
    }

    return $this->content;
  }

}
?>