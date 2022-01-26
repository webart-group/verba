<?php

namespace Verba\Act\Delete\Handler;

use \Verba\Act\Delete\Handler;

/**
 * Class CurRemoveCpprUpdate
 *
 * удаление информации о связках валюта-платежка
 * при удалении валюты
 *
 * @package Act\Delete\Handler
 */
class CurRemoveCpprUpdate extends Handler
{
    function run()
    {
        $Store = \Verba\_mod('store');
        /**
         * @var $Shop \Mod\Shop
         */
        $Shop = \Verba\_mod('shop');
        $curId = $this->row[$this->oh->getPAC()];

        // удаление из cppr таблицы
        $q = "DELETE FROM `".SYS_DATABASE."`.`".$Shop->cppr_table."` 
WHERE `iCurId` = '".$curId."' || `oCurId` = '".$curId."'";
        $this->DB()->query($q);

        // удаление колонки из pc_stores таблицы
        $q = "ALTER TABLE `".SYS_DATABASE."`.`".$Store->cpk_table."` 
DROP COLUMN `pcmin_".$curId."`";

        $sqlr = $this->DB()->query($q);

        return $sqlr->getAffectedRows();
    }
}
