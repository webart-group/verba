<?php

namespace Verba\Mod\User\Authorization;

use Verba\Mod\User\Model\User;

interface AuthResultInterface
{
    public function getIdentity(): ?User;
}
