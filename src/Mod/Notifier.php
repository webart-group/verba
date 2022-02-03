<?php

namespace Mod;

use Mod\Notifier\Event;
use Mod\Notifier\Pipe;

class Notifier extends \Verba\Mod
{
    use \Verba\ModInstance;

    private $_pipes = [];

    /**
     * @param string|null $name
     * @param null $channel
     * @return Pipe|false
     */
    function pipe(string $name = null, $channel = null)
    {
        if(!is_string($name)){
            return new Pipe();
        }

        if(!array_key_exists($name, $this->_pipes) && !$this->createPipe($name, $channel))
        {
            return false;
        }

        return $this->_pipes[$name];
    }

    /**
     * @param string $name
     * @param string $channel
     * @return Pipe
     */
    protected function createPipe(string $name, string $channel)
    {
        $this->_pipes[$name] = new Pipe($name, $channel);

        return $this->_pipes[$name];
    }

    /**
     * @param $channel
     * @param Event|Event[] $events
     * @return bool
     */
    function sendNotify(string $channel, $events) : bool
    {
        /**
         * @var $mCent \Mod\Centrifugo
         */
        $mCent = Centrifugo::getInstance();

        $pipes = [];
        if($events instanceof Event)
        {
            $events = [$events];
        }

        if(is_array($events))
        {
            foreach ($events as $event)
            {
                if(!$event instanceof Event || !$event->pipe)
                {
                    continue;
                }

                if(!array_key_exists($event->pipe, $pipes))
                {
                    $pipes[$event->pipe] = [];
                }

                $pipes[$event->pipe][] = $event->asArray();
            }
        }

        try {
            $mCent->Client()->publish($channel, ['pipes' => $pipes]);
        } catch (\Exception $e) {
            $this->log()->error($e);
            return false;
        }
        return true;
    }

    /**
     * @param $Channel \Mod\Chatik\Channel
     * @param bool $forOt
     */
    function getNotifyChannelName($Channel, $forOt = false)
    {
        /**
         * @var $mUser \Verba\User\User
         */
        $mStore = \Mod\Store::getInstance();
        $mUser = \Verba\_mod('user');
        $_store = \Verba\_oh('store');
        $_usr = \Verba\_oh('user');
        if (!$forOt) {
            list($forOt, $forId) = $Channel->getForWho();
        }

        if ($forOt == $_store->getID()) {
            $r = $mStore->getStoreChannelName($Channel->parts->storeId);
        } elseif ($forOt == $_usr->getID()) {
            $r = $mUser->getChannelName($Channel->parts->userId);
        }

        return isset($r) ? $r : false;
    }
}
