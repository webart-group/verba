<?php

class chatik_channelInit extends \Verba\Block\Json
{


    function build()
    {
        /**
         * @var $mCent
         * @var $mChatik Chatik
         */

        $mCent = \Verba\Mod\Centrifugo::i();
        $mChatik = \Verba\Mod\Chatik::i();

        if (!$mCent->verifyClientToken($_REQUEST['token'], $_REQUEST['user'])) {
            throw  new \Verba\Exception\Building('Error issue');
        }

        $userId = (int)$_REQUEST['user'];
        $channel = $_REQUEST['channel'];

        $U = \Verba\User();

        if (!$userId || $U->getId() != $userId || !$U->active) {
            throw  new \Verba\Exception\Building('User error');
        }

        /**
         * @var $Channel \Verba\Mod\Chatik\Channel\Store
         */
        $Channel = \Verba\Mod\WS\Channel::initObject($channel);

        if (!$Channel || !$Channel->valid()
            || !$Channel->loadable
        ) {
            throw  new \Verba\Exception\Building('Channel initialization error');
        }

        // создание записи о чате
        if (!$Channel->OItem->getId()) {

            $Channel = $mChatik->createChannelItem($Channel);

            if (!$Channel) {
                throw  new \Verba\Exception\Building('Channel creation error');
            }
            // Создание записи о контакте
            $Channel->updateContacts($U);
        }

        $this->content = array(
            'id' => $Channel->OItem->getId(),
        );

        return $this->content;

    }
}
