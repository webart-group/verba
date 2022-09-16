<?php

namespace Verba\Mod\Notifier\Event\Messenger;

use Verba\Mod\Notifier\Event;

class NewMessage extends Event
{
    public $method = 'newMessage';
}