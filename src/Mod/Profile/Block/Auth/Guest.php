<?php
namespace Verba\Mod\Profile\Block\Auth;

use Verba\Block\Html;

class Guest extends Html
{
    public $templates = [
        'content' => 'profile/auth/guest.tpl'
    ];

    function prepare()
    {
        $this->tpl->assign([
            'REGISTRATION_URL' => \Verba\Mod\User::i()->getRegisterUrl(),
            'LOGIN_URL' => \Verba\Mod\User::i()->getLoginPageUrl(),
        ]);
    }
}