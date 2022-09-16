<?php

namespace Verba\Mod\User\RequestHandler;

class Logout extends \Verba\Block\Html
{

    function build()
    {
        /**
         * @var $mUser \Verba\Mod\User
         */
        $mUser = \Verba\_mod('User');
        $mUser->logout();

        $this->addHeader('Location', '/'); //$mUser->getHistoryBackUrl()
    }
}
