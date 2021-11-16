<?php

namespace Verba\Act\Delete\Handler\OType\Attr;

use Act\Delete\Handler;

class Delete extends Handler
{
    function run()
    {
        $_oh = \Verba\_oh($this->row['ot_iid']);
        try{
            $vault_mask = '~~_vlt__~~';

            $q = "ALTER TABLE ".$vault_mask." DROP COLUMN `".$this->DB()->escape($this->row['attr_code'])."`";

            $ohs = array($_oh);
            $dsc = $_oh->getDescendants();
            if(is_array($dsc) && count($dsc)){
                foreach($dsc as $dot){
                    $ohs[] = \Verba\_oh($dot);
                }
            }
            /**
             * @var $coh \Verba\Model
             */
            foreach($ohs as $coh) {
                $sqlr = $this->DB()->query(
                    str_replace($vault_mask, $coh->vltURI(), $q)
                );
                if (!$sqlr->getResult()) {
                    $this->log()->error('Error while remove DB Table field for attr: ' . var_export($this->row['attr_code'], true) . ', ot: ' . var_export($coh->getCode(), true));
                }else{
                    $this->log()->event('Delete Attribute SQL Field \''.$this->row['attr_code'].'\' from Table '.$coh->vltT().'. '.$this->row['attr_id'].'#'.$this->row['attr_code']);
                }
            }

        }catch(Exception $e){
            $this->log()->error($e);
        }

        \Verba\_mod('system')->planeClearCache();
    }
}
