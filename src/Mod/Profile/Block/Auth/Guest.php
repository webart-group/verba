<?php
namespace Mod\Profile\Block\Auth;

use Verba\Block\Html;

class Guest extends Html
{
    public $templates = [
        'content' => 'profile/auth/guest.tpl'
    ];

    function prepare()
    {
        $this->tpl->assign([
            'REGISTRATION_URL' => \Verba\User\User::i()->getRegisterUrl(),
            'LOGIN_URL' => \Verba\User\User::i()->getLoginPageUrl(),
        ]);
    }
}