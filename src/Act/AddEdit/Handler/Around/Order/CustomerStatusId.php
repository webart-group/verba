<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use Act\AddEdit\Handler\Around;

class CustomerStatusId extends Around
{
    function run()
    {
        if($this->action != 'new'
            || ($this->action == 'new' && $this->value !== null)){
            return $this->value;
        }
        $customerId = $this->ah->getExtendedData('customerId');
        /**
         * @var $mCustomer \Customer
         * @var $customerProfile \Mod\Customer\Profile
         */
        $mCustomer = \Verba\_mod('customer');
        $customerProfile = $mCustomer->getProfile($customerId);

        return $customerProfile->recountStatusId($this->ah->getObjectValue('total'));
    }
}
