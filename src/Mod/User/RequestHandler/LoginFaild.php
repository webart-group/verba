<?php

namespace Verba\Mod\User\RequestHandler;

class LoginFaild extends \Verba\Block\Html
{
    public $templates = [
        'login-form' => 'user/forms/login.tpl',
        'login-faild' => '/user/forms/login_faild.tpl'
    ];

    function build()
    {
        $mUser = \Verba\_mod('user');
        $this->tpl->assign(array(
            'CURRENT_LOGIN' => isset($_REQUEST['login']) && !empty($_REQUEST['login'])
                ? htmlspecialchars($_REQUEST['login'])
                : '',
            'LN_FORWARD_URL' => $mUser->getAuthorizationUrl()
        ));
        $this->content = '';//$this->tpl->parse(false, 'login-form');

        return $this->content;
    }
}
