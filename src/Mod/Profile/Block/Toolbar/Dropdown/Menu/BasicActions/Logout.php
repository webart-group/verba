<?php
namespace Verba\Mod\Profile\Block\Toolbar\Dropdown\Menu\BasicActions;

class Logout extends \Verba\Block\Html{

    public $templates = array(
        'content' => 'profile/toolbar/dropdown/menu/basic/logout.tpl',
    );

    function prepare(){
        /**
         * @var $mUser \User
         */
        $mUser = \Verba\_mod('user');
        $this->tpl->assign(array(
            'LOGOUT_URL' => $mUser->getLogoutUrl(),
        ));
    }
}
