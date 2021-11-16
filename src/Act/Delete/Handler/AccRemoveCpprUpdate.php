<?php

namespace Verba\Act\Delete\Handler;

use Act\Delete\Handler;

/**
 * Class AccRemoveCpprUpdate
 * удаление информации о связках валюта-платежка - Pc для магазина.
 * при удалении аккаунта
 *
 * @package Act\Delete\Handler
 */
class AccRemoveCpprUpdate extends Handler
{
    function run()
    {
        return 1;
        /*
        $Store = \Verba\_mod('store');
        $q = "DELETE FROM `".SYS_DATABASE."`.`".$Store->cpk_table."` WHERE `accountId` = '".$row[$oh->getPAC()]."'";
        $sqlr = $this->DB()->query($q);
        return $sqlr->getAffectedRows();
        */
    }
}
