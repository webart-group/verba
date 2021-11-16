<?php
namespace Verba\User\RequestHandler;

class Logout extends \Verba\Block\Html{

  function build(){
    /**
     * @var $mUser \Verba\User\User
     * @var $mGame \Mod\Game
     */
    $mUser = \Verba\_mod('User');
    $mUser->logout();
    $mGame = \Verba\_mod('game');
    $mGame->clearUserCookies();

    $this->addHeader('Location', '/'); //$mUser->getHistoryBackUrl()
  }

}
