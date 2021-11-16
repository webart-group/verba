<?php

namespace Verba\Act\Delete\Handler;

use Act\Delete\Handler;

class DataVault extends Handler
{
    function run()
    {
        $_otype = \Verba\_oh('otype');
        if(!$this->oh->getOT()->getRawVltId()){
            return;
        }
        $q = "SELECT `".$_otype->getPAC()."`, vlt_id FROM ".$_otype->vltURI()." WHERE vlt_id = '".$this->row[$this->oh->getPAC()]."'";
        $sqlr = $this->DB()->query($q);
        if(!$sqlr || $sqlr->getNumRows() > 1){
            return;
        }
        $newName = '___to__delete__'.$this->row['object'];
        $rename_q = "RENAME TABLE `".SYS_DATABASE."`.`".$this->row['object']."` TO `".SYS_DATABASE."`.`".$newName."`";
        $sqlr = $this->DB()->query($rename_q);
        if(!$sqlr || !$sqlr->getResult()){
            $this->log()->error('Unable to rename otype vault $row: '.var_export($this->row, true));
        }
        \Verba\_mod('cron')->addTask('otype', 'cron_dropDeletedVault', array($newName, $this->row), date("Y-m-d H:i:s", (time() + 300/* * 84600*/)));
        return;
    }
}
