<?php

namespace Mod;

use Mod\Notifier\Pipe;

class Chatik extends \Verba\Mod
{
    use \Verba\ModInstance;

    function getBackendUrl()
    {
        return $this->_c['backendUrl'];
    }

    /**
     * @param $Channel \Mod\Chatik\Channel\Store
     * @return bool
     * @throws
     */
    function createChannelItem($Channel)
    {

        if (!$Channel->canBeCreatedByUser()) {
            return false;
        }

        $_channel = \Verba\_oh('chatik_channel');
        $ae = $_channel->initAddEdit('new');
        $ae->setGettedData(array(
            'name' => $Channel->name,
            'namespace' => $Channel->namespace,
        ));
        $ae->addedit_object();
        $Channel = \Mod\WS\Channel::initObject($Channel->parts);
        if (!$Channel->OItem->getId()) {
            return false;
        }
        return $Channel;
    }

    function loadContactsFor($forOt, $forId)
    {
        $_chnl = \Verba\_oh('chatik_channel');
        $_cm = \Verba\_oh('chatik_message');

        $vltURI = $_chnl->vltURI($forOt);

        $_user = \Verba\_oh('user');
        $user_ot_id = $_user->getID();

        $_store = \Verba\_oh('store');
        $store_ot_id = $_store->getID();

        $q = "SELECT a.* 
, chnl.name, chnl.namespace, chnl.lastMessageDate,
usr.picture as upic,
usr.display_name as uname,
str.picture as spic, 
str.title as sname,
COUNT(cm.id) AS unread
FROM " . $vltURI . " as a
LEFT JOIN " . $_chnl->vltURI() . " as `chnl` ON a.p_iid = chnl.id  
LEFT JOIN " . $_user->vltURI() . " AS usr ON a.contactOt = " . $user_ot_id . " && a.contactId = usr." . $_user->getPAC() . "
LEFT JOIN " . $_store->vltURI() . " AS str ON a.contactOt = " . $store_ot_id . " && a.contactId = str.id
RIGHT JOIN " . $_chnl->vltURI($_cm) . " cml ON a.p_iid = cml.p_iid
LEFT JOIN " . $_cm->vltURI() . " cm ON cml.ch_iid = cm.id && cm.created > a.checked
  WHERE
   a.`p_ot_id` = '" . $_chnl->getID() . "'
  && a.`ch_ot_id` = '" . $forOt . "' 
  && a.`ch_iid` = '" . $forId . "'
  GROUP BY a.contactOt, a.contactId
  ORDER BY `chnl`.`lastMessageDate` DESC
  ";
        $items = array();
        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getNumRows()) {
            return array();
        }
        /**
         * @var $mImage \Mod\Image
         */
        $mImage = \Verba\_mod('image');

        $iCfg['upic'] = $mImage->getImageConfig('user');
        $iCfg['spic'] = $mImage->getImageConfig('store');

        while ($row = $sqlr->fetchRow()) {
            $contUid = $row['p_ot_id'] . '_' . $row['p_iid'] . '_' . $row['ch_ot_id'] . '_' . $row['ch_iid'];
            $items[$contUid] = array(
                'gid' => $contUid,
                'name' => $row['name'],
                'ot' => $row['ch_iid'],
                'iid' => $row['ch_ot_id'],
                'updated' => $row['lastMessageDate'],
                'title' => '',
                'pic' => '',
                'checked' => $row['checked'],
                'unreadMessages' => (int)$row['unread'],
            );

            $pkey = false;
            $nkey = false;
            if ($row['contactOt'] == $user_ot_id) {

                $pkey = 'upic';
                $nkey = 'uname';

            } elseif ($row['contactOt'] == $store_ot_id) {

                $pkey = 'spic';
                $nkey = 'sname';

            }

            if ($pkey) {
                $items[$contUid]['pic'] = $iCfg[$pkey]->getFullUrl(basename($row[$pkey]), 'ico32');
                $items[$contUid]['title'] = $row[$nkey];
            }
        }

        return $items;
    }

    /**
     * @param int|string $forOt
     * @param int $forId
     */
    function getUnreadMsgsCount($forOt, $forId, $channelId = false)
    {

        $oh = \Verba\_oh($forOt);
        $forOt = $oh->getID();
        $forId = (int)$forId;

        if (!is_numeric($forOt) || !is_int($forId)) {
            return 0;
        }

        $where = "cc.ch_ot_id = " . $forOt . " && cc.ch_iid = " . $forId;

        $channelId = 0;

        $_ch = \Verba\_oh('chatik_channel');
        $_chmsg = \Verba\_oh('chatik_message');
        $_user = \Verba\_oh('user');
        $q = "SELECT SUM(unreadMsgs) FROM (SELECT 
  cc.*,  
  COUNT(cml.ch_iid) AS unreadMsgs,
  cm.created
  FROM " . $_ch->vltURI($_user) . " cc 
  LEFT JOIN " . $_ch->vltURI($_chmsg) . " cml ON cc.p_iid = cml.p_iid
  LEFT JOIN " . $_chmsg->vltURI() . " cm ON cm.id = cml.ch_iid
  WHERE " . $where . "
  && cml.ch_ot_id = " . $_chmsg->getID() . " 
  && cm.created > cc.checked
  GROUP BY cc.p_iid) AS aaa";

        $sqlr = $this->DB()->query($q);

        if (!$sqlr || !$sqlr->getNumRows()) {
            return 0;
        }
        return (int)$sqlr->getFirstValue();
    }

    function genNotifierCfgFor($for)
    {
        if ($for != 'user' && $for != 'store') {
            return false;
        }
        $args = array_slice(func_get_args(), 1);
        return call_user_func_array([$this, 'genNotifierCfgFor' . ucfirst($for)], $args);

    }

    function genNotifierCfgForUser($U = null)
    {
        /**
         * @var $mUser User
         */
        $mUser = \Verba\_mod('user');
        return array(
            'channel' => $mUser->getChannelName($U),
            'pipe' => Pipe::ALIAS_USER,
            'priority' => 100,
        );
    }

    function genNotifierCfgForStore($Store)
    {
        $mStore = \Mod\Store::getInstance();
        return array(
            'channel' => $mStore->getStoreChannelName($Store),
            'pipe' => Pipe::ALIAS_STORE,
            'priority' => 100,
        );
    }
}

Chatik::$_config_default = array(

    'backendUrl' => '/chatik/'

);


?>