<?php

namespace Verba\User\RequestHandler;

class Logout extends \Verba\Block\Html
{

    function build()
    {
        /**
         * @var $mUser \Verba\User\User
         */
        $mUser = \Verba\_mod('User');
        $mUser->logout();

        $this->addHeader('Location', '/'); //$mUser->getHistoryBackUrl()
    }
}
