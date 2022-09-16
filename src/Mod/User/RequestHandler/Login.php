<?php

namespace Verba\Mod\User\RequestHandler;

class Login extends \Verba\Block\Json
{

    function build()
    {
        $mUser = \Verba\_mod('User');
        try {

            /**
             * @var $mUser Verba\Mod\User
             * @var $mGame Verba\Mod\Game
             */

            if ($mUser->authNow()) {
                $this->content = $mUser->getHistoryBackUrl();
            } else {
                throw new Exception(Lang::get('user auth common_error'));
            }
        } catch (Exception $e) {
            $this->setOperationStatus(false);
            $this->content = $e->getMessage();
        }

        return $this->content;
    }
}
