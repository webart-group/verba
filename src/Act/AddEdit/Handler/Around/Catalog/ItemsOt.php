<?php

namespace Verba\Act\AddEdit\Handler\Around\Catalog;

use Act\AddEdit\Handler\Around;

class ItemsOt extends Around
{
    function run()
    {
        // если конфиг строка
        if($this->value === null){
            return null;
        }
        if(!\Verba\isOt($this->value)){
            $oh = false;
            $ot_id = 0;
            $ot_code = '';
        }else{
            $oh = \Verba\_oh($this->value);
            $ot_id = $oh->getID();
            $ot_code = $oh->getCode();
        }

        if($this->action == 'edit'){
            $exs = (int)$this->getExistsValue('itemsOtId');
        }

        // if catalog OT changed or empty - delete all catalog products
        if(isset($exs) && $exs && $exs !== $ot_id){
            $this->ah->listen('beforeComplete', 'OTChanged', $this, null, $exs);
        }

        $this->ah->setGettedObjectData(array('itemsType' => $ot_code));

        return $ot_id;
    }

    function OTChanged($exOt)
    {
        if(!$exOt){
            $exOt = \Verba\_oh('product')->getID();
        }
        $_p = \Verba\_oh($exOt);
        $_c = \Verba\_oh('catalog');

        $dh = $_p->initDelete();
        $dh->setParent($_c, $this->ah->getIID());
        $dh->delete_objects();
        \Verba\_mod('system')->planeClearCache();
    }
}
