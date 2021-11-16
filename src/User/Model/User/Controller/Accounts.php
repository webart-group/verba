<?php

namespace Verba\User\Model\User\Controller;


class Accounts extends \Verba\Base
{

    protected $userId;
    protected $accounts = null;

    function __construct($userId)
    {
        $userId = (int)$userId;
        if (!$userId || $userId < 1) {
            return;
        }
        $this->userId = $userId;
    }

    function loadAccounts()
    {
        $this->accounts = array();
        if (!$this->userId) {
            return false;
        }
        $_account = \Verba\_oh('account');
        $_user = \Verba\_oh('user');

        $qm = new \Verba\QueryMaker($_account, false, true);
        $cond = $qm->addConditionByLinkedOTRight($_user, $this->userId);
        $qm->addOrder(array('priority' => 'd'));
        $qm->addOrder(array('id' => 'a'));
        $q = $qm->getQuery();

        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getNumRows()) {
            return null;
        }
        while ($row = $sqlr->fetchRow()) {
            $this->accounts[$row[$_account->getPAC()]] = new \Mod\Account\Model\Account($row);
        }
    }

    function getAccount($iid = false)
    {
        $accs = $this->getAccounts();

        if (!$iid) {
            reset($accs);
            $iid = key($accs);
        }

        return array_key_exists($iid, $accs) ? $accs[$iid] : null;
    }

    function getAccounts()
    {
        if ($this->accounts === null) {
            $this->loadAccounts();
        }

        return $this->accounts;
    }

    function getAccountsAsArrays($onlyActive = false, $onlyVisibleCurrencies = false)
    {
        $this->getAccounts();
        $r = array();
        if (!$this->accounts) {
            return $r;
        }
        /**
         * @var $Acc \Mod\Account\Model\Account
         */
        foreach ($this->accounts as $Acc) {
            $Cur = $Acc->getCurrency();
            if (!$Cur
                || ($onlyVisibleCurrencies == true && $Cur->hidden)
                || ($onlyActive == true && !$Acc->active)) {
                continue;
            }

            $r['i' . $Acc->getId()] = $Acc->exportForPublic();
        }

        return $r;
    }

    function getAccountByCur($currencyId)
    {

        if (is_object($currencyId) && $currencyId instanceof \Model\Currency) {
            $currencyId = $currencyId->getId();
        }
        $currencyId = (int)$currencyId;
        if (!$currencyId) {
            return false;
        }

        $accs = $this->getAccounts();
        /**
         * @var $cAcc \Mod\Account\Model\Account
         */
        foreach ($accs as $cAcc) {
            if ($cAcc->currencyId == $currencyId) {
                return $cAcc;
            }
        }
        return false;
    }
}


