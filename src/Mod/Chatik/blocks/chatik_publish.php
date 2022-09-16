<?php

class chatik_publish extends \Verba\Block\Json
{
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
        $messageInput = is_string($_REQUEST['message']) ? trim($_REQUEST['message']) : false;
        $clientId = $_REQUEST['cid'];

        if (empty($messageInput) || !$clientId) {
            throw  new \Verba\Exception\Building('Bad data');
        }

        $U = \Verba\User();

        if (!$userId || $U->getId() != $userId || !$U->active) {
            throw  new \Verba\Exception\Building('User error');
        }

        /**
         * @var $Channel \Verba\Mod\Chatik\Channel\Store
         */
        $Channel = \Verba\Mod\WS\Channel::initObject($channel);
        if (!$Channel || !$Channel->valid()) {
            //Канал не существует в БД
            if ($Channel->OItem === false && $Channel->parts->isValid() && $Channel->userHasAccess()) {
                // Создаем канал в бд
                $Channel = $mChatik->createChannelItem($Channel);
                if (!$Channel) {
                    throw  new \Verba\Exception\Building('Unable to initialize chat channel');
                }

                // Создание записи о контакте
                $Channel->updateContacts($U);
            } else {
                throw  new \Verba\Exception\Building('Invalid channel');
            }
        }

        $Message = $Channel->publish($messageInput, $U);

        if (!$Message) {
            throw  new \Verba\Exception\Building('Message creation error');
        }

        $Message->addInfo(array('cid' => $clientId));

        $this->content = array(
            'id' => $Message->getId(),
        );
        $mCent->Client()->publish($Channel->name,
            [
                'content' => $Message->content,
                'info' => $Message->getInfo()
            ]);

        $Channel->messagePublished($Message);

        list($forOt, $forId) = $Channel->getForWho();

        $Channel->updateChecked(strtotime($Message->created), $forOt, $forId);

        return $this->content;
    }
}
