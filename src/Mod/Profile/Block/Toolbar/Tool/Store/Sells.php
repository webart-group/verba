<?php
namespace Verba\Mod\Profile\Block\Toolbar\Tool\Store;

use Verba\Mod\Notifier\Pipe;

class Sells extends \Verba\Mod\Profile\Block\Toolbar\Tool\Store{
    public $url = '/profile/sells';
    public $badge = array(
        'color' => 'blue',
    );

    public $icon = array(
        'src' => 'my-sell',
        'w' => 17,
        'h' => 26,
    );

    public $cssClass = 'sells';

    public $notifierAgent = array(
        'pipe' => Pipe::ALIAS_STORE,
        'className' => 'NotifyAgentStore',
    );

    function loadNotifyCount(){
        /**
         * @var $mStore \Verba\Mod\Store
         */
        $mStore = \Verba\_mod('store');
        $count = $mStore->countOpenedOrders((int)$this->U->getValue('storeId'));
        return $count;
    }
}