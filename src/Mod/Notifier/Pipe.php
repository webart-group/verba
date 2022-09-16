<?php

namespace Verba\Mod\Notifier;

use \Verba\Mod\Notifier;

class Pipe
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $channel;

    public const ALIAS_USER = 'user';
    public const ALIAS_STORE = 'store';

    function __construct(string $name = null, $channel = null)
    {
        if(isset($name))
        {
            $this->name = $name;
        }

        if(isset($channel))
        {
            $this->channel = $channel;
        }
    }

    function send($events, $channel = null)
    {
        $r = [];
        if($events instanceof Event)
        {
            $events = [$events];
        }

        if(!is_array($events))
        {
            return false;
        }

        foreach ($events as $event)
        {
            if(!$event instanceof Event)
            {
                continue;
            }
            $event->pipe = $this->name;

            $r[] = $event;
        }

        return Notifier::i()->sendNotify(isset($channel) ? $channel : $this->channel, $r);
    }
}