<?php

namespace Verba\Act\AddEdit\Handler\Around\Account;

use Act\AddEdit\Handler\Around;

/**
 * Class ModeOneStableWithUnstable
 *
 * В случае редактирования с 1158 в другой режим проверяет чтобы оставался в режиме ввода
 * хотя бы один кошелек со стабильной валютой.
 *
 * @package Act\AddEdit\Handler\Around\Account
 */
class ModeOneStableWithUnstable extends Around
{
    function run()
    {

        if($this->action != 'edit' || !in_array($this->value, array(1158, 1159, 1160))){
            return $this->value;
        }

        // Это изменение, новое значение - 1159 или 1160

        $_acc = \Verba\_oh('account');
        $_cur = \Verba\_oh('currency');
        $ownerField = $_acc->getOwnerAttributeCode();
        $pac = $_acc->getPAC();
        $ownerId = $this->ah->getActualValue($ownerField);
        $accCur =  \Mod\Currency::getInstance()->getCurrency($this->ah->getActualValue('currencyId'));

        $q = "SELECT 
  SUM(IF(c.unstable = 0, 0, 1)) AS `unstable_count`,
  SUM(IF(c.unstable = 0, 1, 0)) AS `stable_count`
  
  FROM ".$_acc->vltURI()." a
  LEFT JOIN ".$_cur->vltURI()." c ON a.`currencyId` = c.`id`
  WHERE 
   `a`.`".$ownerField."` = '".\Verba\esc($ownerId)."' 
    && `a`.`".$pac."` != '".$this->ah->getIID()."'
    && `a`.mode = 1158";

        $sqlr = $this->DB()->query($q);

        if(!$sqlr || $sqlr->getNumRows() < 1){
            $this->log()->error(\Lang::get('error data_handle'));
            return false;
        }

        $row = $sqlr->fetchRow();
        $row['unstable_count'] = (int)$row['unstable_count'];
        $row['stable_count'] = (int)$row['stable_count'];
        // если это "только вывод" или блокировка кошелька
        // и при этом остаются только кошельки с нестабильной валютой
        if((($this->value == 1159 || $this->value == 1160) && $row['unstable_count'] > 0 && $row['stable_count'] == 0)
            // или это включение счета с нестабильной валютой
            // и при этом нет включенных стабильных кошельков
            || ($this->value == 1158 && $accCur->unstable == 1 && $row['stable_count'] == 0)
        ) {
            $this->log()->error(\Lang::get('account warns stable-currency-account'));
            return false;
        }

        return $this->value;
    }
}
