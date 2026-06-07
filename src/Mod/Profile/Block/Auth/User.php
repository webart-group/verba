<?php
namespace Verba\Mod\Profile\Block\Auth;

use Verba\Block\Html;

class User extends Html
{
    public $templates = [
        'content' => 'profile/auth/user.tpl',
    ];

    function prepare(){
        $U = \Verba\User();
        $displayName = $U->display_name;
        if(!$displayName){
            $displayName = '-';
        }

        if(!$userpicUrl = $U->getUserpic()){
            $userpicUrl = '/assets/img/icons/icon-logged.svg';
        }

        $this->tpl->assign(array(
            'USER_DISPLAY_NAME' => htmlspecialchars($displayName),
            'USERPIC_URL' => $userpicUrl,
        ));
    }
}