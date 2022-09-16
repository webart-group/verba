<?php

namespace Verba\Act\AddEdit\Handler\Around\Account;

use \Verba\Act\AddEdit\Handler\Around;

/**
 * Class ActivePresetByPossibleOwnerStore
 *
 * account Active
 *
 * только NEW и только если не передано значение
 * При создании счета ищет магазин пользователя и смотрит его валюту
 * Если валюта кошелька и магазина совпадают значение active = 1
 *
 * @package Verba\Act\AddEdit\Handler\Around\Account
 */
class ActivePresetByPossibleOwnerStore extends Around
{
    function run()
    {
        if($this->action != 'new'
            || $this->value !== null){
            return $this->value;
        }

        try {
            $active = null;
            // По умолчанию
            $ownerId = $this->ah->getTempValue('owner');
            $currencyId = $this->ah->getTempValue('currencyId');
            $_store = \Verba\_oh('store');

            $qm = new \Verba\QueryMaker($_store, false, array('currencyId'));
            $qm->addWhere($ownerId, 'owner');
            $qm->addWhere(1, 'active');
            $qm->addLimit(1);
            $sqlr = $qm->run();

            if($sqlr && $sqlr->getNumRows()){

                $store = $sqlr->fetchRow();
                $storeCurId = $store['currencyId'];

                if(isset($storeCurId)
                    && $storeCurId == $currencyId
                    && is_object($c =  \Verba\Mod\Currency::getInstance()->getCurrency($currencyId))
                    && $c->active)
                {
                    $active = 1;
                }
            }

        }catch (\Exception $e){
            $this->log()->error($e);
            return false;
        }

        return isset($active) ? $active : $this->value;
    }
}
