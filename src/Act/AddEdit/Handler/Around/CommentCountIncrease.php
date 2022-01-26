<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class CommentCountIncrease extends Around
{
    function run()
    {
        $this->ah->listen('afterLink', 'increase', $this);
    }

    function increase(){
        $pot = $this->ah->getFirstParentOt();
        $piid = $this->ah->getFirstParentIid();
        $active = $this->ah->getTempValue('active');
        if($this->action == 'edit'){
            if(!isset($active) || $active == ($prev_action = $this->getExistsValue('active'))){
                return;
            }
            // search for any parent
            if(!$pot){
                $br = \Verba\Branch::get_branch(array($this->oh->getID() => array(
                    'iids' => $this->ah->getIID())), 'up', 1, true, false);
                if(!count($br['handled']) || empty($br['handled'])){
                    return;
                }
                $pot = key($br['handled']);
                $piid = current($br['handled'][$pot]);
            }
            $val = $active ? '+1' : '-1';
        }else{
            if(!$active){
                return;
            }
            $val = '+1';
        }

        if(!$pot || !$piid){
            return;
        }

        $_parent = \Verba\_oh($pot);

        $ae = $_parent->initAddEdit(array('action' => 'edit'));
        $ae->setIID($piid);
        $ae->setGettedObjectData(array('comments_count' => $val));
        $ae->addedit_object();

        return;
    }
}
