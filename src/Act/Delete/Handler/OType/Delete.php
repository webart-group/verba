<?php

namespace Verba\Act\Delete\Handler\OType;

use \Verba\Act\Delete\Handler;

class Delete extends Handler
{
    function run()
    {
        \Verba\_mod('system')->planeClearCache();
    }
}
