<?php

namespace Mod\Profile\Block\Toolbar\Tool\User;

use Mod\Notifier\Pipe;

class Purchases extends \Verba\Mod\Profile\Block\Toolbar\Tool\User
{

    public $url = '/profile/purchases';

    public $badge = array(
        'color' => 'green',
    );

    public $icon = array(
        'src' => 'my-buy',
        'w' => 20,
        'h' => 26,
    );

    public $cssClass = 'purchases';

    public $notifierAgent = [
        'pipe' => Pipe::ALIAS_USER,
        'className' => 'NotifyAgentUserToolPurchases',
    ];

    function loadNotifyCount()
    {
        /**
         * @var $mCustomer \Customer
         */
        $mCustomer = \Verba\_mod('customer');
        $count = $mCustomer->countOpenedOrders($this->U->getId());
        return $count;
    }

}