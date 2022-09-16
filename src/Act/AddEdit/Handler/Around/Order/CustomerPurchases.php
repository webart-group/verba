<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use \Verba\Act\AddEdit\Handler\Around;

class CustomerPurchases extends Around
{
    function run()
    {
        if($this->action != 'new'
            || ($this->action == 'new' && $this->value !== null)){
            return $this->value;
        }
        $customerId = $this->ah->getExtendedData('customerId');
        /**
         * @var $customerProfile \Verba\Mod\Customer\Profile
         */
        $customerProfile = \Verba\_mod('customer')->getProfile($customerId);
        return $customerProfile->getTotalPurchases();
//    $_order = \Verba\_oh('order');
//    $q = "SELECT COUNT(*) tp FROM ".$_order->vltURI()." WHERE customerId = '".$this->DB()->escape_string($customerId)."' && status ='21'";
//    $sqlr = $this->DB()->query($q);
//    if(!$sqlr || !$sqlr->getNumRows()){
//      return 0;
//    }
//    $row = $sqlr->fetchRow();
//    return (int)$row['tp'];
    }
}
