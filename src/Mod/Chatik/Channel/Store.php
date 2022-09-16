<?php

namespace Verba\Mod\Chatik\Channel;

class Store extends \Verba\Mod\Chatik\Channel
{

    /**
     * @var $Store \Model\Store
     */
    public $Store;

    function load()
    {
        parent::load();

        $this->getStore();

        return $this->OItem;
    }

    /**
     * @param $U \Verba\Mod\User\Model\User
     */
    function getContactsStruct($U)
    {
        return array(
            $this->Store->oh()->getID() => array(
                'id' => $this->Store->getId(),
                'contactOt' => \Verba\_oh('user')->getID(),
                'contactId' => $U->getId(),
            ),
            \Verba\_oh('user')->getID() => array(
                'id' => $U->getId(),
                'contactOt' => $this->Store->oh()->getID(),
                'contactId' => $this->Store->getId(),
            )
        );

    }

    /**
     * @param $U \Verba\Mod\User\Model\User
     * @return mixed|null
     */
    function customMessageInfoGenerator($userData)
    {

        if (!$this->Store instanceof \Model\Store
            || !is_array($userData) || !array_key_exists('userId', $userData)) {
            return false;
        }

        if ($this->Store->owner != $userData['userId']) {
            return false;
        }
        $info = array();
        $info['nick'] = htmlspecialchars($this->Store->getValue('title'));
        $info['pic'] = $this->Store->getImageAttrUrl('ico32');
        $info['userId'] = $userData['userId'];

        return $info;

    }

    function setStore($Store)
    {
        if (!$Store instanceof \Model\Store || !$Store->getId()) {
            return false;
        }

        $this->Store = $Store;
        return $this->Store;
    }

    function getStore()
    {
        if ($this->Store === null) {
            $this->Store = $this->loadStore();
        }
        return $this->Store;
    }

    function loadStore()
    {

        if (!$this->parts->storeId || !($Store = \Verba\_mod('store')->OTIC()->getItem($this->parts->storeId))
            || !$Store->getId()
        ) {
            return false;
        }

        return $Store;
    }

    /**
     * @param $for string|null|bool Для магазина или персоны - store|pers
     * @return array|bool
     */
    function getForWho($for = null)
    {

        if ($for === null) {
            $for = isset($_REQUEST['for']) ? $_REQUEST['for'] : false;
        }

        if (is_string($for) && $for == 'store') {
            if ($this->Store->owner &&\Verba\User()->getId() == $this->Store->owner) {
                return array($this->Store->oh()->getID(), $this->Store->getId());
            }
            return array(false, false);
        }

        return parent::getForWho();
    }

    function userHasAccess($U = null)
    {
        if ($U === null) {
            $U = \Verba\User();
        }
        if ($U instanceof \Verba\Mod\User\Model\User) {
            $userId = $U->getId();
        } else {
            $userId = (int)$U;
        }

        $this->getStore();

        return $userId && ($userId == $this->Store->owner || $this->parts->userId == $userId);
    }

    function canBeCreatedByUser($U = false)
    {
        if (!$U || !$U instanceof \Verba\Mod\User\Model\User) {
            $U = \Verba\User();
        }

        return $this->userHasAccess($U);
    }

}

?>