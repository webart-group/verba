<?php
namespace Verba\Mod\User\RequestHandler;

class PasswordResetSendCode extends \Verba\Block\Json{

  public function build(){

    $_user = \Verba\_oh('user');
    /**
     * @var $mUser User
     */
    $mUser = \Verba\_mod('User');
    $email = strtolower($_REQUEST['email']);

    $qm = new \Verba\QueryMaker($_user->getID(), false, array('email', 'last_password_reset_time'));
    $qm->addWhere("`email` = '".$this->DB()->escape_string($email)."'");
    $qm->addLimit(1);
    $qm->makeQuery();
    $sqlr = $qm->run();
    if(!$sqlr || $sqlr->getNumRows() !== 1){
      throw new Exception(Lang::get('user reclaim_pass email-not-found'));
    }

    $userData = $sqlr->fetchRow();
    $userId = $userData[$_user->getPAC()];

    list($pswdTimeValid, $interval) = $mUser->validatePasswordResetTime($userData['last_password_reset_time']);
    if(!$pswdTimeValid){
      throw  new \Verba\Exception\Building(Lang::get('user reclaim_pass delayed', array(
          'hours' => $interval->h,
          'minutes' => $interval->i)
      ));
    }

    $code = $mUser->genPassResetCode($userId);
    if(!$code){
      throw new Exception(Lang::get('error error'));
    }

    $ae = $_user->initAddEdit(array('iid' => $userId));
    $ae->setGettedData(array(
      'password_reset_code' => $code,
      'last_password_reset_time' => time(),
    ));
    $ae->addedit_object();
    if($ae->haveErrors()){
      throw new Exception(Lang::get('error process'));
    }

    $_text = \Verba\_oh('textblock');
    $email_template = $_text->getData('password_reset_link');
    if(!$email_template){
      $email_template = array(
        'title' => 'Passwd changing. '.SYS_THIS_HOST,
        'text' => 'Passwd changing link {PASSWORD_RESET_URL}',
      );
    }

    $this->tpl->assign(array(
      'PASSWORD_RESET_CODE' => $code,
      'THIS_HOST' => SYS_THIS_HOST,
    ));
    /**
     * @var $mMail CoMail
     */
    $mMail = \Verba\_mod('comail');
    /**
     * @var $mail \Verba\Mod\CoMail\PHPMailer
     */
    $mail = $mMail->PHPMailer();

    $mail->setSubject($this->tpl->parse_template($email_template['title']));
    $mail->MsgHTML($this->tpl()->parse_template($email_template['text']));

    $mail->AddAddress($userData['email']);

    if(!$mMail->Send($mail, true)){
      $this->log()->error($mail->ErrorInfo);
      throw new Exception(Lang::get('error error'));
    }

    $this->content = \Verba\Lang::get('user reclaim_pass letter sent');

    return $this->content;
  }

}
?>