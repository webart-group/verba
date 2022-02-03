<?php

use \Mod\Notifier\Pipe;
use \Mod\Notifier\Event\Messenger\VUpd;

class chatik_messagesHistory extends \Verba\Block\Json
{


    public $forOt;
    public $forId;

    function build()
    {
        /**
         * @var $mCent Centrifugo
         * @var $mChatik Chatik
         */

        $mCent = \Verba\_mod('centrifugo');
        $mChatik = \Verba\_mod('Chatik');

        if (!$mCent->verifyClientToken($_REQUEST['token'], $_REQUEST['user'])) {
            throw  new \Verba\Exception\Building('Error issue');
        }

        $userId = (int)$_REQUEST['user'];
        $channel = $_REQUEST['channel'];

        if (empty($channel) || !$userId) {
            throw  new \Verba\Exception\Building('Bad data');
        }

        $U = User();

        if (!$userId || $U->getId() != $userId || !$U->active) {
            throw  new \Verba\Exception\Building('User error');
        }

        /**
         * @var $Channel \Mod\Chatik\Channel\Store
         */
        $Channel = \Mod\WS\Channel::initObject($channel);
        if (!$Channel || !$Channel->valid()) {
            //Канал не существует в БД
            if ($Channel->OItem === false && $Channel->parts->isValid() && $Channel->userHasAccess()) {
                // Создаем канал в бд
                $Channel = $mChatik->createChannelItem($Channel);
                if (!$Channel) {
                    throw  new \Verba\Exception\Building('Unable to initialize chat channel');
                }
                $channelJustCreated = true;
                // Создание записи о контакте
                $Channel->updateContacts($U);
            } else {
                throw  new \Verba\Exception\Building('Invalid channel');
            }
        }

        $messages = $Channel->loadMessagesFrom($_REQUEST['from']);
        $this->content = $messages;

        // Обновление информации о непрочитанных сообщениях
        if (!$_REQUEST['from'] && !isset($channelJustCreated)) {

            list($forOt, $forId) = $Channel->getForWho();
            $Channel->updateChecked(time(), $forOt, $forId);

            /**
             * @var $mNotifier \Mod\Notifier
             */
            $mNotifier = \Verba\_mod('notifier');
            $_store = \Verba\_oh('store');

            $channel = $mNotifier->getNotifyChannelName($Channel, $forOt);

            $mNotifier->pipe(
                $forOt == $_store->getID() ? Pipe::ALIAS_STORE : Pipe::ALIAS_USER,
                $channel
            )
                ->send(new VUpd(['data' => $mChatik->getUnreadMsgsCount($forOt, $forId)]));

        }

        return $this->content;

    }
}
