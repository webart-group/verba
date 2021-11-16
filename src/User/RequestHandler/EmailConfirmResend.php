<?php
namespace Verba\User\RequestHandler;

class EmailConfirmResend extends \Verba\Block\Json{

  function build(){

    $_user = \Verba\_oh('user');
      /**
       * @var $mUser User
       */
    $mUser = \Verba\_mod('User');
    $U = User();
    $userData = $_user->getData($U->getID(), 1);
    if(!$userData){
      throw  new \Verba\Exception\Building('Bad params');
    }
    $timout = (int)$mUser->gC('email_confirmation_resend_timeout');
    if(!$timout){
      $timout = 3600;
    }
    $last_request_time = (int)$userData['last_confirmation_request_time'];
    $now = time();
    if($last_request_time > 0 && $now - $last_request_time < $timout) {
      $dateNow = new DateTime(date('Y-m-d H:i', $now));
      $dateNext = new DateTime(date('Y-m-d H:i', $last_request_time + $timout));
      $interval = $dateNext->diff($dateNow);

      throw  new \Verba\Exception\Building(Lang::get('user email_confirm delayed', array(
        'hours' => $interval->h,
        'minutes' => $interval->i)
      ));
    }

    if(!$mUser->sendEmailConfirmationLink($userData)){
      throw  new \Verba\Exception\Building('Resend error');
    }
    $this->content = \Verba\Lang::get('user email_confirm sent');

    return $this->content;

  }

}
?>