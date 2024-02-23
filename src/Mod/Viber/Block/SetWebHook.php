<?php

namespace Verba\Mod\Viber\Block;

use Verba\Block\Json;
use Verba\QueryMaker;
use function Verba\_oh;
use function Verba\_mod;

class SetWebHook extends Json
{
    function build()
    {
        return _mod('viber')->setWebHook();
    }
}
