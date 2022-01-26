<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class GetCustomerId extends Around
{
    function run()
    {
        if($this->action != 'new'){
            return null;
        }
        /**
         * @var $mCustomer \Customer
         */
        $mCustomer = \Verba\_mod('customer');
        $customerProfile = $mCustomer->getProfile();

        return $customerProfile->getId();
    }
}
