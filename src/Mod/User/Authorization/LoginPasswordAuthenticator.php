<?php

namespace Verba\Mod\User\Authorization;

use Verba\Mod\User;
use Verba\QueryMaker;
use function Verba\_mod;
use function Verba\_oh;

class LoginPasswordAuthenticator implements AuthenticatorInterface
{
    protected ?string $login;
    protected ?string $password;

    public function __construct(string $login, string $password)
    {
        $this->login = $login;
        $this->password = $password;
    }

    public function authorize()
    {
        $U = $this->findIdentity();
        return $U;
    }

    function findIdentity(): User\Model\User
    {
        $_user = _oh('user');
        /**
         * @var $mUser User
         */
        $mUser = _mod('User');

        $field = $mUser->gC('login_field');

        $qm = new QueryMaker($_user, false, ['id', 'password']);
        $qm->addSelectPastFrom($_user->getPAC(), null, 'id');
        $qm->addWhere($this->login, $field);
        $qm->addWhere(1, 'active');

        $sqlr = $qm->run();

        if (!is_object($sqlr) || !$sqlr->getNumRows()) {
            throw new \Exception('User not found');
        }
        $userData = $sqlr->fetchRow();

        if (!password_verify($this->password, $userData['password'])) {
            throw new \Exception('Wrong password');
        }

        return new User\Model\User($userData['id']);
    }
}
