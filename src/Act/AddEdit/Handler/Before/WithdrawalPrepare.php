<?php

namespace Verba\Act\AddEdit\Handler\Before;

use Act\AddEdit\Handler\Before;

/**
 *  Обработчик срабатывает перед созданием записи withdrawal и проверяет/выставляет ключевые параметры.
 */
class WithdrawalPrepare extends Before
{
    //protected $allowed = array('new');
    protected $_allowedEdit = false;

    function run()
    {
        $U = \Verba\User();

        if (!$U->getAuthorized()) {
            throw new \Exception('Access denied');
        }

        $accId = $this->ah->getGettedValue('accountId');
        /**
         * @var $Acc \Mod\Account\Model\Account
         */
        $Acc = $U->Accounts()->getAccount($accId);

        // Проверка что аккаунт существует
        if (!$Acc) {
            throw new \Exception('Bad account');
        }
        // Сумма применима?
        if (!$Acc->isSumApproved(-1 * $this->ah->getGettedValue('sum'))) {
            throw new \Exception($Acc->log()->getLastError());
        }

        $_preq = \Verba\_oh('prequisite');
        // Получение реквизитов
        $Preq = $_preq->initItem($this->ah->getGettedValue('prequisiteId'));
        if (!$Preq || !$Preq->active) {
            throw new \Exception('Bad preq');
        }

        // Сравнение валюты реквизита и аккаунта
        if (!$Acc->currencyId || $Acc->currencyId != $Preq->getRawValue('currencyId')) {
            throw new \Exception('Currency mismath');
        }
        /**
         * @var $cur \Verba\Model\Currency
         */
        $cur = \Verba\_mod('currency')->getCurrency($Acc->currencyId);
        if (!$cur->p('active')) {
            throw new \Exception('Currency is inactive');
        }

        $Pay = \Verba\_mod('payment')->getPaysys($Preq->paysysId);

        if (!$cur
            || !($Pay && $Pay->active)
            || !$cur->isPaysysLinkExists($Pay->getId(), 'output')) {
            throw new \Exception('Bad params');
        }

        $this->ah->setGettedData(array(
            'currencyId' => $Acc->currencyId,
            'paysysId' => $Pay->getId(),
            'accountId' => $Acc->getId(),
            'account' => $Preq->account
        ));

        return true;
    }

}
