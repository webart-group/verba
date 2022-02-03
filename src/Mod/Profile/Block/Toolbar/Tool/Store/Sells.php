<?php
namespace Mod\Profile\Block\Toolbar\Tool\Store;

use Mod\Notifier\Pipe;

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
         * @var $mStore \Mod\Store
         */
        $mStore = \Verba\_mod('store');
        $count = $mStore->countOpenedOrders((int)$this->U->getValue('storeId'));
        return $count;
    }
}