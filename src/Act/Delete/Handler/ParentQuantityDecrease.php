<?php

namespace Verba\Act\Delete\Handler;

use Act\Delete\Handler;

class ParentQuantityDecrease extends Handler
{
    function run()
    {
        if(!isset($this->params['parent_ot'])
            || !($poh = \Verba\_oh($this->params['parent_ot']))){
            return false;
        }
        $iid = $this->row[$this->oh->getPAC()];
        $ot_id = $this->oh->getId();
        $pot = $poh->getId();
        $br = \Verba\Branch::get_branch(array($ot_id => array('iids' => $iid, 'aot' => $pot)), 'up');
        if(!isset($br['handled'][$pot]) || empty($br['handled'][$pot])){
            return false;
        }
        foreach ($br['handled'][$pot] as $cpiid) {
            $ae = $poh->initAddEdit(array('action' => 'edit'));
            $ae->setIID($cpiid);
            $ae->setGettedObjectData(array('quantity_avaible' => '-1'));
            $ae->addedit_object();
        }

        return true;
    }
}
