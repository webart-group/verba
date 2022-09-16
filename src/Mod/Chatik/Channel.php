<?php

namespace Verba\Mod\Chatik;

use Verba\Mod\Notifier\Event;
use Verba\Mod\Notifier\Event\Messenger\NewMessage;
use Verba\Mod\Notifier\Pipe;

class Channel extends \Verba\Mod\WS\Channel
{

    protected $_cachedUsersData = [];

    public $loadable = true;

    function valid()
    {
        return $this->OItem !== null && (!is_object($this->OItem) || !$this->OItem->getId())
            ? false
            : parent::valid();
    }

    function load()
    {
        $_ch = \Verba\_oh('chatik_channel');
        $this->OItem = $_ch->initItem($this->parts->name);
        return $this->OItem;
    }

    /**
     * @param $message string
     * @param $U \Verba\Mod\User\Model\User
     * @return bool|Model\Message
     * @throws
     */
    function publish($message, $U)
    {
        if (!$this->OItem->active || !$U || !$U->active || !$U->getAuthorized()) {
            return false;
        }

        if (!is_string($message) || empty($message)) {
            return false;
        }

        $_chmessage = \Verba\_oh('chatik_message');

        $ae = $_chmessage->initAddEdit('new');
        $ae->setGettedData(array(
            'content' => $message,
        ));

        $ae->addParents($this->OItem->oh()->getID(), $this->OItem->getId());
        $message_iid = $ae->addedit_object();
        if (!$message_iid) {
            return false;
        }

        $Message = new Model\Message($ae->getActualData());

        $Message->addInfo($this->generateMessageInfo($U));


        $this->OItem->update(array(
            'totalMessages' => $this->OItem->totalMessages++,
            'lastMessageDate' => $Message->created,
        ));

        return $Message;
    }

    /**
     * @param $Message Model\Message
     * @return bool
     * @throws
     */
    function messagePublished($Message)
    {
        //Отправка оповещений в канал Пользователя и магазина
        /**
         * @var $mNotifier \Verba\Mod\Notifier
         * @var $mUser \Verba\Mod\User
         */
        $mNotifier = \Verba\_mod('notifier');
        $mStore = \Verba\Mod\Store::getInstance();
        $mUser = \Verba\_mod('user');

        $event = new NewMessage([
            'id' => $Message->getId(),
            'userId' => (int)$Message->owner,
            'timestamp' => strtotime($Message->created),
            'channel' => $this->parts->name
        ]);

        $mNotifier->pipe(Pipe::ALIAS_STORE, $mStore->getStoreChannelName($this->parts->storeId))
            ->send($event);

        $mNotifier->pipe(Pipe::ALIAS_USER, $mUser->getChannelName($this->parts->userId))
            ->send($event);

        return true;
    }

    /**
     * @param $U
     * @return bool|null
     * @throws \Exception
     */
    function updateContacts($U)
    {

        $contactsStruct = $this->getContactsStruct($U);

        if (!is_array($contactsStruct) || !count($contactsStruct)) {
            return null;
        }
        $channelOtId = $this->OItem->oh()->getID();
        $channelId = $this->OItem->getId();
        $values = array();

        foreach ($contactsStruct as $chot => $chdata) {
            $values[] = '(' . $channelOtId . ',' . $channelId . ',' . $chot . ',' . $chdata['id'] . ',' . $chdata['contactOt'] . ',' . $chdata['contactId'] . ')';
        }
        $_user = \Verba\_oh('user');
        $q = "INSERT IGNORE INTO " . $this->OItem->oh()->vltURI($_user) . "
     (
     `p_ot_id`, 
     `p_iid`, 
     `ch_ot_id`, 
     `ch_iid`, 
     `contactOt`,
     `contactId`
     )
     VALUES " . implode(",\n", $values);

        $this->DB()->query($q);

        return true;
    }

    function getContactsStruct($U)
    {
        return false;
    }

    /**
     * @param $U \Verba\Mod\User\Model\User
     * @return mixed|null
     */
    function extractInfoDataFromU($U)
    {
        $r = array(
            'display_name' => '',
            'picture' => '',
            'userId' => false,
        );
        if (!$U instanceof \Verba\Mod\User\Model\User
            || !$U->getAuthorized()) {
            return $r;
        }

        $r['display_name'] = $U->getValue('display_name');
        $r['picture'] = $U->picture;
        $r['userId'] = (int)$U->getId();

        return $r;
    }

    /**
     * @param $arg \Verba\Mod\User\Model\User|array
     * @return mixed|null
     */
    function generateMessageInfo($userData)
    {


        if (is_object($userData) && $userData instanceof \Verba\Mod\User\Model\User) {
            $userData = $this->extractInfoDataFromU($userData);
        }

        if (!is_array($userData) || !array_key_exists('userId', $userData)) {
            return false;
        }

        if (array_key_exists($userData['userId'], $this->_cachedUsersData)) {
            return $this->_cachedUsersData[$userData['userId']];
        }

        $info = $this->customMessageInfoGenerator($userData);

        if (!is_array($info)) {
            $info = array();

            $info['nick'] = array_key_exists('display_name', $userData)
                ? htmlspecialchars($userData['display_name'])
                : '__unknown_user';

            if (!empty($userData['picture'])) {
                /**
                 * @var $mImage \Verba\Mod\Image
                 */
                $mImage = \Verba\_mod('Image');
                $iCfg = $mImage->getImageConfig('user');
                $info['pic'] = $iCfg->getFullUrl(basename($userData['picture']), 'ico32');
            } else {
                $info['pic'] = null;
            }

            $info['userId'] = $userData['userId'];
        }

        $this->_cachedUsersData[$userData['userId']] = $info;

        return $info;
    }

    function customMessageInfoGenerator($userData)
    {
        return false;
    }

    function loadMessagesFrom($from = false)
    {
        $from = (int)$from;
        if (!$from) {
            $from = time();
        }

        $date = date('Y-m-d H:i:s', $from);
        if (!$date) {
            return array();
        }

        $_msgs = \Verba\_oh('chatik_message');
        $_user = \Verba\_oh('user');

        $q = "SELECT m.*, 
`u`.`display_name`,
`u`.`picture`
FROM " . $_msgs->vltURI() . " as `m`

RIGHT JOIN " . $_msgs->vltURI($this->OItem->oh()) . " as `ml` 
ON ml.ch_iid = `m`.id

LEFT JOIN " . $_user->vltURI() . " `u` 
ON `u`.`" . $_user->getPAC() . "` = `m`.`owner`
 
WHERE m.created < '" . $date . "'
&& ml.p_iid = '" . $this->OItem->getId() . "'

ORDER BY m.created DESC

LIMIT 10
";
        $sqlr = $this->DB()->query($q);

        $r = array();

        if (!$sqlr || !$sqlr->getNumRows()) {
            return $r;
        }

        while ($row = $sqlr->fetchRow()) {
            $r['i' . $row['id']] = $this->generateHistoryMessage($row);
        }

        return $r;
    }

    function generateHistoryMessage($row)
    {

        $Message = new Model\Message($row);
        $Message->addInfo($this->generateMessageInfo(
            array(
                'userId' => $row['owner'],
                'display_name' => $row['display_name'],
                'picture' => $row['picture']
            )
        ));
        $data = array(
            'data' => array(
                'content' => $row['content'],
                'info' => $Message->getInfo()
            )
        );
        return $data;
    }

    function getForWho()
    {
        $U = \Verba\User();
        if (!$U instanceof \Verba\Mod\User\Model\User || !$U->getAuthorized() || !$U->active) {
            return false;
        }

        return array(\Verba\_oh('user')->getID(), $U->getId());
    }

    function updateChecked($chtime = null, $forOt = false, $forId = false)
    {
        $chtime = (int)$chtime;
        if (!$chtime) {
            $chtime = time();
        }
        $sqlDateStr = date('Y-m-d H:i:s', $chtime);

        if (!$sqlDateStr) {
            return false;
        }

        if (!$forOt) {
            list($forOt, $forId) = $this->getForWho();
        }

        if (!$forOt) {
            return false;
        }

        $_ch = \Verba\_oh('chatik_channel');
        $_user = \Verba\_oh('user');

        $q = "UPDATE " . $_ch->vltURI($_user) . " SET checked = '" . $sqlDateStr . "' 
    WHERE `p_ot_id` = '" . $_ch->getID() . "' 
    && `p_iid` = '" . $this->OItem->getIid() . "' 
    && `ch_ot_id` = '" . $forOt . "'
    && `ch_iid` = '" . $forId . "'";

        $sqlr = $this->DB()->query($q);
        if (!$sqlr) {
            return false;
        }

        return true;

    }

    /**
     * @param bool $U \Verba\Mod\User\Model\User
     * @return bool
     */
    function canBeCreatedByUser($U = false)
    {
        return false;
    }
}
