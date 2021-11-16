<?php

namespace Verba\Act\Delete\Handler\OType;

use Act\Delete\Handler;

class Delete extends Handler
{
    function run()
    {
        \Verba\_mod('system')->planeClearCache();
    }
}
