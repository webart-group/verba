<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class MenuUrl extends Around
{
    function run()
    {
        if(!isset($this->value)){
            return null;
        }
        $this->value = (string)$this->value;
        if($this->ah->getGettedObjectData('inherit_url'))
        {
            $inherit = ($inherit = $this->ah->getGettedValue('inherit_url')) !== null
                ? $inherit
                : $this->getExistsValue('inherit_url');

            $prefix = \Verba\_mod('menu')->detectMenuItemParentPrefix($this->ah, $this->A, $inherit);
            if(is_string($prefix) && !empty($prefix)
                && mb_strpos($this->value, $prefix) !== 0)
            {
                $this->value = $prefix.ltrim($this->value, '/');
            }
        }
        // update linked content code by url
        if($this->action == 'edit' && $this->value != $this->getExistsValue('url')){
            $_cont = \Verba\_oh('content');
            $_menu = \Verba\_oh('menu');
            $qm = new \Verba\QueryMaker($_cont, false, true);
            $qm->addConditionByLinkedOT($_menu, $this->ah->getIID());
            $qm->addLimit(1);
            $sqlr = $qm->run();
            if($sqlr && $sqlr->getNumRows()){
                $cont = $sqlr->fetchRow();
                $newVal = $this->value;
                if(($slashpos = mb_strrpos($this->value, '/')) !== false){
                    $newVal = mb_substr($this->value, ++$slashpos);
                }
                $ae = $_cont->initAddEdit(array(
                    'action' => 'edit',
                    'iid' => $cont[$_cont->getPAC()],
                ));
                $ae->setGettedObjectData(array('id_code' => $newVal));
                $ae->addedit_object();
            }
        }
        return $this->value;
    }
}