<?php

namespace Verba\Act\Delete\Handler;

use Act\Delete\Handler;

/**
 * Class StoreRemoveCpprUpdate
 *
 * удаление информации о связках валюта-платежка - Pc для магазина.
 * при удалении магазина
 *
 * @package Act\Delete\Handler
 */
class StoreRemoveCpprUpdate extends Handler
{
    function run()
    {
        $Store = \Verba\_mod('store');

        $q = "DELETE FROM `".SYS_DATABASE."`.`".$Store->cpk_table."` WHERE `storeId` = '".$this->row[$this->oh->getPAC()]."'";

        $sqlr = $this->DB()->query($q);

        return $sqlr->getAffectedRows();
    }
}
