<?php
namespace Verba\User\RequestHandler;

class Specify extends \Verba\Block\Json{

  function build(){

    try{

      /**
       * @var $mCaptcha Captcha
       */

//      $mCaptcha = \Verba\_mod('captcha');
//      if(!$mCaptcha->useCurrentCaptcha()){
//        throw new Exception(Lang::get('captcha wrong'));
//      }

      $mUser = \Verba\_mod('User');
      $url = \Verba\Hive::getBackURL();
      $login = strtolower(trim($_REQUEST['login']));
      $_user = \Verba\_oh('user');
      $qm = new \Verba\QueryMaker($_user, false, false);
      $qm->addWhere($login, $mUser->gC('login_field'));
      $sqlr = $qm->run();
      if(!$sqlr){
        throw  new \Verba\Exception\Building('Unable to run query');
      }
      // Участинк с таким Email ненайден
      // Автосоздание нового участника
      if(!$sqlr->getNumRows()){

        $password = $password_confirm = \Verba\Hive::make_random_string(8,8);

        $createUser = new user_create($this, array(
          'data' => array(
            $mUser->gC('login_field') => $login,
            'password' => array(
              0 => $password,
              1 => $password_confirm,
            )
          )
        ));
        $createUser->run();

        // Если участинк создан и авторизирован
        if($createUser->getOperationStatus() === false){
          $this->failed($createUser->content);
          return $this->content;
        }

        $this->content = 'created';

      // Email (user) существуют, отправка соотв ответа
      }else{

        $this->content = 'exists';

      }

    }catch(Exception $e){
      $this->failed($e->getMessage());
    }
    return $this->content;

  }

}
?>