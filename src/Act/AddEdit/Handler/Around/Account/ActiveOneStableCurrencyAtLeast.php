<?php

namespace Verba\Act\AddEdit\Handler\Around\Account;

use \Verba\Act\AddEdit\Handler\Around;

/**
 * Class ActiveOneStableCurrencyAtLeast
 *
 * В случае редактирования проверяет чтобы оставалась хотя бы одна
 * включенная стабильная валюта. Если такой нет - включает значение.
 *
 * @package Verba\Act\AddEdit\Handler\Around\Account
 */
class ActiveOneStableCurrencyAtLeast extends Around
{
    function run()
    {
        if($this->action != 'edit' || $this->value == 1){
            return $this->value;
        }

        $_acc = \Verba\_oh('account');
        $_cur = \Verba\_oh('currency');
        $ownerField = $_acc->getOwnerAttributeCode();
        $pac = $_acc->getPAC();
        $ownerId = $this->ah->getActualValue($ownerField);

        $q = "SELECT a.id 
    FROM ".$_acc->vltURI()." a
    LEFT JOIN ".$_cur->vltURI()." c ON a.`currencyId` = c.`id`
    WHERE 
    `a`.`".$ownerField."` = '".\Verba\esc($ownerId)."' 
    && `a`.`".$pac."` != '".$this->ah->getIID()."'
    && `c`.`unstable` = 0
    && `a`.`active` = 1
    LIMIT 1";

        $sqlr = $this->DB()->query($q);

        if(!$sqlr || $sqlr->getNumRows() < 1){
            $this->log()->error(\Verba\Lang::get('account warns stable-currency-account'));
            return false;
        }
        return $this->value;
    }
}
