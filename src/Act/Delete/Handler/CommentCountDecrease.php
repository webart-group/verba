<?php

namespace Verba\Act\Delete\Handler;

use Act\Delete\Handler;

class CommentCountDecrease extends Handler
{
    function run()
    {
        if($this->row['active'] != 1){
            return;
        }

        $ot_id = $this->oh->getId();
        $iid = $this->row[$this->oh->getPAC()];
        $br = \Verba\Branch::get_branch(array($ot_id => array('iids' => $iid)), 'up', 1, false,false);
        if(!count($br['handled']) || empty($br['handled'])){
            return;
        }
        $pot = key($br['handled']);
        $piid = current($br['handled'][$pot]);
        $_parent = \Verba\_oh($pot);

        $ae = $_parent->initAddEdit(array('action' => 'edit'));
        $ae->setIID($piid);
        $ae->setGettedObjectData(array('comments_count' => '-1'));
        $ae->addedit_object();

        return;
    }
}
