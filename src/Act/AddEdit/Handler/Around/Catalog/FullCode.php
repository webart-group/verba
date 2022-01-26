<?php

namespace Verba\Act\AddEdit\Handler\Around\Catalog;

use \Verba\Act\AddEdit\Handler\Around;

class FullCode extends Around
{
    function run()
    {
        $_ext_def = array(
            'parent' => array(
                'id' => false,
                'fullcode' => false,
            )
        );

        $local = $this->ah->getExtendedData('__fullCode');
        if(!$local){
            $local = $_ext_def;
        }

        $_c = \Verba\_oh('catalog');
        $ot_id = $_c->getID();

        $piid = false;
        $p = $this->ah->getParents();
        if(isset($p[$ot_id])
            && is_array($p[$ot_id])
            && !empty($p[$ot_id])){
            $piid = current($p[$ot_id]);
        }

        $actual_code = $this->ah->getActualValue('code');
        $exists_code = $this->ah->getExistsValue('code');
        $exists_fullcode = (string)$this->getExistsValue('fullcode');
        $exists_fullcode_mode = (bool)$this->getExistsValue('fullcode_mode');
        $parent_fullcode = '';

        if(is_numeric($local['parent']['id']) && is_string($local['parent']['fullcode'])){

            $parent_fullcode = $local['parent']['fullcode'];

            // Данные по паренту не переданы, но есть parent id - загружаем фул-код парента
        }else{
            if (!$piid) {

                $q = "SELECT `ot_id`, `key_id`, `id`, `fullcode` 
        FROM ".$_c->vltURI()." as `c`
        WHERE id IN (
          SELECT p_iid 
          FROM ".$_c->vltURI($_c)." 
          WHERE p_ot_id = '".$_c->getID()."'
           && `ch_ot_id` = '".$_c->getID()."'
           && `ch_iid` = '".$this->ah->getIID()."'
           )
        LIMIT 1";
                $sqlr = $this->DB()->query($q);

                $parentData = $sqlr->fetchRow();

            }else{
                $parentData = $_c->getData($piid, 1,array('fullcode'));
            }

            if(is_array($parentData) && count($parentData) ){
                $parent_fullcode = $parentData['fullcode'];
            }else{
                $parent_fullcode = '';
            }

        }

        $this->value = $exists_fullcode_mode ? $parent_fullcode . '/' .$actual_code : $parent_fullcode;

        //if($exists_code != $actual_code){
            $this->ah->listen('beforeComplete', 'updatePropaganda', $this);
        //}

        return $this->value;
    }

    function updatePropaganda() {

        if($this->action == 'new'){
            return;
        }

        $iid = $this->ah->getIID();
        $_c = \Verba\_oh('catalog');
        $ot_id = $_c->getID();
        $br = \Verba\Branch::get_branch(array($ot_id => array('aot' => $ot_id, 'iids' => $iid)), 'down', 1, false, false, 'd', false);
        if(is_array($br['handled'][$ot_id]) && count($br['handled'][$ot_id])){
            $_extData = array(
                '__fullCode' => array(
                    'parent' => array(
                        'id' => $iid,
                        'fullcode' => $this->ah->getActualValue('fullcode'),
                    )
                ),
            );

            $items = $_c->getData($br['handled'][$ot_id], true);

            foreach($items as $itemId => $itemData){

                if(!is_array($itemData)
                    || !$itemData
                    || !array_key_exists($_c->getPAC(), $itemData)
                    || !$itemData[$_c->getPAC()]
                ){
                    \Verba\Loger::create(__METHOD__)->error('Unexists entry '.$ot_id.' #'.$itemId.', for parent: '.$ot_id.' #'.$iid);
                    continue;
                }

                $ae = $_c->initAddEdit(array('action' => 'edit'));
                $ae->setIID($itemId);
                $ae->setExistsValues($itemData);

                $ae->setGettedObjectData(array(
                    'fullcode' => 'N',
                ));

                $ae->addExtendedData($_extData);

                $ae->addedit_object();

                if(!$ae->haveErrors()){
                    $this->log()->error('Unable to update \'fullcode\' attribute to catalog#'.$itemId);
                }
            }
        }
    }
}
