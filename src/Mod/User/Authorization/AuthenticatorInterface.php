<?php

namespace Verba\Mod\User\Authorization;

use Verba\Mod\User\Model\User;

interface AuthenticatorInterface
{
    public function authorize();
}
