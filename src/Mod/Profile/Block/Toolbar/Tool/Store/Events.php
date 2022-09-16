<?php
namespace Verba\Mod\Profile\Block\Toolbar\Tool\Store;

use Verba\Mod\Notifier\Pipe;

class Events extends \Verba\Mod\Profile\Block\Toolbar\Tool\Store{
    public $url = '/store/msgs';

    public $badge = array(
        'color' => 'orange',
    );

    public $icon = array(
        'src' => 'my-chat-shop',
        'w' => 26,
        'h' => 26,
    );

    public $cssClass = 's-msgs';

    public $notifierAgent = array(
        'pipe' => Pipe::ALIAS_STORE,
        'className' => 'NotifyAgentStore',
    );

    function init(){
        parent::init();

        $this->url = $this->storeUrlBase.'/msgs';
    }

    function loadNotifyCount(){
        /**
         * @var $mChatik \Chatik
         */
        $mChatik = \Verba\_mod('chatik');

        $count = $mChatik->getUnreadMsgsCount('store', $this->U->getValue('storeId'));
        return $count;
    }
}