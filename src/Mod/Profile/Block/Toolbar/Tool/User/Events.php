<?php
namespace Mod\Profile\Block\Toolbar\Tool\User;

use Mod\Notifier\Pipe;

class Events extends \Verba\Mod\Profile\Block\Toolbar\Tool\User{

    public $url = '/profile/msgs';

    public $badge = array(
        'color' => 'orange',
    );

    public $icon = array(
        'src' => 'my-chat',
        'w' => 26,
        'h' => 26,
    );
    public $code = 'pm';
    public $cssClass = 'msgs';

    public $notifierAgent = [
        'pipe' => Pipe::ALIAS_USER,
        'className' => 'NotifyAgentUserToolMsgs',
    ];


    function loadNotifyCount(){
        /**
         * @var $mChatik \Mod\Chatik
         */
        $mChatik = \Verba\_mod('chatik');

        return $mChatik->getUnreadMsgsCount('user',$this->U->getId());
    }
}