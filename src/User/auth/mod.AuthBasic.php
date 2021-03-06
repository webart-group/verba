<?php

class AuthBasic extends \Verba\Mod
{

    function authorizeNow($authData)
    {

        $_user = \Verba\_oh('user');
        /**
         * @var $mUser User
         */
        $mUser = \Verba\_mod('User');
        $login = isset($authData['login']) ? trim(strtolower($authData['login'])) : false;
        $password = isset($authData['password']) ? $authData['password'] : false;
        if (!is_string($login) || !is_string($password)) {
            return false;
        }

        $qm = new \Verba\QueryMaker($_user, false, true);
        $qm->addSelectPastFrom($_user->getPAC(), null, 'id');
        $qm->addWhere($login, $mUser->gC('login_field'));
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
