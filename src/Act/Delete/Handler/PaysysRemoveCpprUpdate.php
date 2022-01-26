<?php

namespace Verba\Act\Delete\Handler;

use \Verba\Act\Delete\Handler;

/**
 * Class PaysysRemoveCpprUpdate
 *
 * удаление информации о связках валюта-платежка.
 * при удалении платежки
 *
 * @package Act\Delete\Handler
 */
class PaysysRemoveCpprUpdate extends Handler
{
    function run()
    {
        /**
         * @var $Store \Mod\Store
         * @var $Shop \Mod\Shop
         */
        $Store = \Verba\_mod('store');
        $Shop = \Verba\_mod('shop');
        $psId = $this->row[$this->oh->getPAC()];

        // удаление из cppr таблицы
        $q = "DELETE FROM `".SYS_DATABASE."`.`".$Shop->cppr_table."` 
WHERE `iPaysysId` = '".$psId."'";
        $sqlr = $this->DB()->query($q);
        return $sqlr->getAffectedRows();
    }
}
