<?php

namespace Verba\Mod\Acp\Tabset;

use Verba\Mod\Acp\Tab\TabList\PdSetsList;

class PdSets extends \Verba\Mod\Acp\Tabset
{

    function tabs()
    {
        return [
            PdSetsList::class
        ];
    }
}
