<?php
namespace Verba\Mod\User\RequestHandler;

class PasswordResetNow extends \Verba\Block\Json{

  public function build(){
    try {
      $_user = \Verba\_oh('user');
      /**
       * @var $mUser User
       */
      $mUser = \Verba\_mod('User');
      $email = strtolower($this->rq->getParam('email'));
      $code = $this->rq->getParam('code');
      $code = is_string($code) && !empty($code)
        ? strtoupper(trim($code))
        : false;

      $pwd = $this->rq->getParam('pwd');
      if (!is_string($pwd)) {
        $pwd = false;
      }
      $pwd_cnf = $this->rq->getParam('pwd_cnf');
      if (!is_string($pwd_cnf)) {
        $pwd_cnf = false;
      }

      if (!$email || !$code || !is_string($pwd) || !is_string($pwd_cnf) ) {
        throw new Exception(Lang::get('error bad_data'));
      }

      $qm = new \Verba\QueryMaker($_user, false, array('last_password_reset_time', 'email', 'password_reset_code', 'password'));
      $qm->addWhere($email, 'email');
      $qm->addWhere($code, 'password_reset_code');
      $qm->addWhere(1, 'active');
      $qm->makeQuery();
      $sqlr = $qm->run();
      if (!$sqlr || $sqlr->getNumRows() !== 1) {
        throw new Exception(Lang::get('user reclaim_pass email-not-found'));
      }

      $userData = $sqlr->fetchRow();
      $userId = $userData[$_user->getPAC()];

//      list($pswdTimeValid, $interval) = $mUser->validatePasswordResetTime($userData['last_password_reset_time']);
//      if(!$pswdTimeValid){
//        throw new Exception(Lang::get('user reclaim_pass delayed', array(
//            'hours' => $interval->h,
//            'minutes' => $interval->i)
//        ));
//      }

      $ae = $_user->initAddEdit(array('iid' => $userId));
      $existsPwdObj = new stdClass();
      $existsPwdObj->password_reset_code = $userData['password_reset_code'];

      $ae->setGettedData(array(
        'password' => array(
          0 => $pwd,
          1 => $pwd_cnf,
          2 => $existsPwdObj
        ),
        'last_password_reset_time' => time(),
        'password_reset_code' => '',
      ));
      $ae->addedit_object();
      if ($ae->haveErrors()){
        throw new Exception($ae->log()->getMessagesAsString('error'));
      }
      $mUser->authNow(false, $email, $pwd);
      $this->content = \Verba\Hive::getBackURL();

    }catch(Exception $e){
      $this->failed($e->getMessage());
    }
    return $this->content;
  }

}
?>