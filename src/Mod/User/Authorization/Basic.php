<?php

namespace Verba\Mod\User\Authorization;

use Verba\Mod\User;
use Verba\QueryMaker;
use function Verba\_mod;
use function Verba\_oh;

class Basic
{
    function authorize($authData)
    {
        $_user = _oh('user');
        /**
         * @var $mUser User
         */
        $mUser = _mod('User');
        $login = isset($authData['login']) ? trim(strtolower($authData['login'])) : false;
        $password = isset($authData['password']) ? $authData['password'] : false;
        if (!is_string($login) || !is_string($password)) {
            return false;
        }
        $field = $mUser->gC('login_field');

        $qm = new QueryMaker($_user, false, true);
        $qm->addSelectPastFrom($_user->getPAC(), null, 'id');
        $qm->addWhere($login, $field);
        $qm->addWhere(1, 'active');

        $sqlr = $qm->run();

        if (!is_object($sqlr)
            || !$sqlr->getNumRows()) {
            return false;
        }
        $userData = $sqlr->fetchRow();

        if (!$mUser->pwdVerify($password, $userData['password'])) {
            return false;
        }

        return $userData;
    }
}
