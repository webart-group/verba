<?php

namespace Verba\Act\AddEdit\Handler\After;

use Act\AddEdit\Handler\After;
use \Verba\Model\Store;

class AccUpdateCpprUpdate extends After
{

    public $accData;
    /**
     * @var Store
     */
    public $Store;
    public $accountId;

    function run()
    {
        $_acc = \Verba\_oh('account');

        $this->accData = $this->ah->getActualData();

        // узнаем владельца кошелька
        $ownerId = $this->accData[$_acc->getOwnerAttributeCode()];

        // ищем его магазин
        $_store = \Verba\_oh('store');

        $qm = new \Verba\QueryMaker($_store, false, true);
        $qm->addWhere($ownerId, $_store->getOwnerAttributeCode());

        $sqlr = $qm->run();
        //магазина нет - выход
        if (!$sqlr || !$sqlr->getNumRows()) {
            return null;
        }
        // берем первый магазин
        $this->Store = new Store($sqlr->fetchRow());

        $this->accountId = $this->accData[$_acc->getPAC()];

        if ($this->action == 'new'
            || ($this->ah->isUpdated()
                && is_array($updData = $this->ah->getUpdatedData())
                && (array_key_exists('active', $updData) || array_key_exists('mode', $updData))
            )) {
            $Store = \Mod\Store::getInstance();
            $Store->refreshStoreCPK($this->Store);
        }

        return true;
    }
}
