<?php

namespace Verba\Mod\User;

class Router extends \Verba\Request\Http\Router
{
    function route()
    {
        $rq = $this->rq;
        switch ($rq->node) {
            case 'loginfaild':
//                $b = new \Verba\Mod\User\RequestHandler\LoginFaild($rq);
//                break;
            case 'letmein':
                $b = new \Verba\Mod\User\RequestHandler\Login($rq);
                break;
            case 'profile':
                $b = new \Verba\Mod\User\RequestHandler\Profile($rq);
                break;
//            case 'logout':
//                $b = new \Verba\Mod\User\RequestHandler\Logout($rq);
//                break;
//            case 'create':
//            case 'new':
//                $b = new \Verba\Mod\User\RequestHandler\Create($rq);
//                break;
//            case 'specify':
//                $b = new \Verba\Mod\User\RequestHandler\Specify($rq);
//                break;
//            case 'login-form':
//                $b = new \Verba\Mod\User\User\Block\Login\Json();
//                break;
//            case 'pwd-reset-code-request':
//                $b = new \Verba\Mod\User\RequestHandler\PasswordResetSendCode($rq);
//                break;
//            case 'reset-pwd-form':
//                $b = new \Verba\Mod\User\RequestHandler\PasswordResetForm($rq);
//                break;
//            case 'reset-pwd':
//                $b = new \Verba\Mod\User\RequestHandler\PasswordResetNow($rq);
//                break;
//            case 'password-restore':
//                $b = new \Verba\Mod\User\RequestHandler\PasswordForgotenForm($rq);
//                break;
//            case 'email-confirm':
//                $b = new \Verba\Mod\User\RequestHandler\EmailConfirm($rq);
//                break;
//            case 'email-confirm-resend':
//                $b = new \Verba\Mod\User\RequestHandler\EmailConfirmResend($rq);
//                break;
//            case 'morf':
//                $b = new \Verba\Mod\User\RequestHandler\Morf($rq);
//                break;
        }

        if (!isset($b)) {
            throw new \Verba\Exception\Routing();
        }

        return $b->route();
    }

}
