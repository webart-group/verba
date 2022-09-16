<?php
namespace Verba\Mod;

class System extends \Verba\Mod
{
    use \Verba\ModInstance;

    function clearCache()
    {
        global $S;
        $S->sC(1, '__clearCache');
        $S->clearCache();
    }

    function planeClearCache()
    {
        global $S;
        $S->sC(1, '__clearCache');
    }
}
