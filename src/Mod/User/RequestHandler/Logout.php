<?php

namespace Verba\Mod\User\RequestHandler;

class Logout extends \Verba\Block\Json
{

    function build()
    {
        /**
         * @var $mUser \Verba\Mod\User
         */
        $mUser = \Verba\_mod('User');
        $mUser->logout();

        $this->content = true;

        return $this->content;
    }
}
