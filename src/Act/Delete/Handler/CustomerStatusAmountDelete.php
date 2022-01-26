<?php

namespace Verba\Act\Delete\Handler;

use \Verba\Act\Delete\Handler;

class CustomerStatusAmountDelete extends Handler
{
    function run()
    {
        /**
         * @var $mCron \Cron
         */
        $mCron = \Verba\_mod('cron');
        $mCron->addTask('customer', 'cron_recountCutomerStatuses');
        return true;
    }
}
