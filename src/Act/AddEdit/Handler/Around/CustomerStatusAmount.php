<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class CustomerStatusAmount extends Around
{
    function run()
    {
        if($this->action == 'edit'
            && $this->getExistsValue($this->A->getCode()) == $this->value){
            return $this->value;
        }
        /**
         * @var $mCron \Cron
         */
        $mCron = \Verba\_mod('cron');
        $mCron->addTask('customer', 'cron_recountCutomerStatuses');

        return $this->value;
    }
}
