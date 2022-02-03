<?php

namespace Mod\Notifier\Event\Messenger;

use Mod\Notifier\Event;

class NewMessage extends Event
{
    public $method = 'newMessage';
}