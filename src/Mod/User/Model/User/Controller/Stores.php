<?php

namespace Verba\Mod\User\Model\User\Controller;

class Stores extends \Verba\Base
{

    protected $userId;
    protected $_stores = null;

    function __construct($U)
    {
        \Verba\_mod('store');
        $this->userId = $U->getId();
    }

    function loadStores()
    {
        $this->_stores = array();
        if (!$this->userId) {
            return false;
        }
        $_store = \Verba\_oh('store');
        $_user = \Verba\_oh('user');

        $qm = new \Verba\QueryMaker($_store, false, true);
        $cond = $qm->addConditionByLinkedOTRight($_user, $this->userId);
        $qm->addOrder(array('priority' => 'd'));
        $qm->addOrder(array('id' => 'a'));
        $q = $qm->getQuery();

        $sqlr = $this->DB()->query($q);
        if (!$sqlr) {
            throw new \Exception('Unable to load Stores');
        }

        while ($row = $sqlr->fetchRow()) {
            $this->_stores[$row[$_store->getPAC()]] = new \Model\Store($row);
        }
    }

    function getStore($iid = false)
    {
        if ($this->_stores === null) {
            $this->loadStores();
        }

        if (!$iid) {
            reset($this->_stores);
            $iid = key($this->_stores);
        }

        return array_key_exists($iid, $this->_stores) ? $this->_stores[$iid] : null;
    }

    function getStores()
    {
        if ($this->_stores === null) {
            $this->_stores = array();
            $this->loadStores();
        }

        return $this->_stores;
    }

    function haveStores()
    {
        $this->getStores();
        return !is_array($this->_stores) || !count($this->_stores)
            ? false
            : true;
    }

}