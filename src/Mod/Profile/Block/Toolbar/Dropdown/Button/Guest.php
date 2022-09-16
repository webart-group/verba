<?php
namespace Verba\Mod\Profile\Block\Toolbar\Dropdown\Button;


class Guest extends \Verba\Block\Html {
    public $templates = [
        'content' => 'profile/toolbar/dropdown/button/guest.tpl',
    ];

    function prepare(){
        $this->tpl->assign([
            'USER_LOGIN_PAGE_URL' => \Verba\Mod\User::i()->getLoginPageUrl(),
        ]);
    }
}
