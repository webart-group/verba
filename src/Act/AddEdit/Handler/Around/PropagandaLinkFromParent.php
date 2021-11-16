<?php

namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class PropagandaLinkFromParent extends Around
{
    function run()
    {
        $_linkedOh = \Verba\_oh($this->params['linkedOt']);

        $ot_id = $this->oh->getID();
        // id каталога-родителя
        $cat_piid = false;
        // ot_id третьего объекта
        $lot_id = $_linkedOh->getID();
        //id текущего объекта
        $iid = $this->ah->getIID();

        $exists_liid = null;
        // id третьего объекта, с которым связан родитель
        // именно с этим id надо связать текущий объект
        $linked_iid = false;
        $p = $this->ah->getParents();


        // ищем парент третьего объекта
        // среди указанных прямо

        if(isset($p[$lot_id])
            && is_array($p[$lot_id])
            && !empty($p[$lot_id])){
            reset($p[$lot_id]);
            $linked_iid = current($p[$lot_id]);
        }

        if(!$linked_iid){
            $linked_iid = $this->ah->getFromToLinkFirstIid($_linkedOh);
        }

        // каталог парента
        if(isset($p[$ot_id])
            && is_array($p[$ot_id])
            && !empty($p[$ot_id])){
            reset($p[$ot_id]);
            $cat_piid = current($p[$ot_id]);
        }
        $parent_linked_iid = false;

        // каталога парента нет
        // в прямо указанных, пытаемся достать его и сразу
        // id третьего объекта по цепочке

        // текущий liid
        if($this->action == 'edit') {

            $br = \Verba\Branch::get_branch(array(
                $ot_id => array(
                    'aot' => array($lot_id, $ot_id,),
                    'iids' => $iid)),
                'up', 2, true, false, 'd', false);

            if (isset($br['pare'][$ot_id][$iid][$lot_id])
                && is_array($br['pare'][$ot_id][$iid][$lot_id])
                && count($br['pare'][$ot_id][$iid][$lot_id])
            ) {
                $exists_liid = current($br['pare'][$ot_id][$iid][$lot_id]);
            }

            // $parent_liid
            if(!$cat_piid && isset($br['pare'][$ot_id][$iid][$ot_id])
                && is_array($br['pare'][$ot_id][$iid][$ot_id])
                && count($br['pare'][$ot_id][$iid][$ot_id])) {

                $cat_piid = current($br['pare'][$ot_id][$iid][$ot_id]);
            }

        }else{
            if($cat_piid){ // new
                $br = \Verba\Branch::get_branch(array(
                    $ot_id => array(
                        'aot' => array($lot_id),
                        'iids' => $cat_piid)),
                    'up', 1, true, false, 'd', false);

                if(isset($br['pare'][$ot_id][$cat_piid][$lot_id])
                    && is_array($br['pare'][$ot_id][$cat_piid][$lot_id])
                    && count($br['pare'][$ot_id][$cat_piid][$lot_id])){

                    $parent_linked_iid = current($br['pare'][$ot_id][$cat_piid][$lot_id]);
                }
            }
        }

        if($cat_piid && isset($br['pare'][$ot_id][$cat_piid][$lot_id])
            && is_array($br['pare'][$ot_id][$cat_piid][$lot_id])
            && count($br['pare'][$ot_id][$cat_piid][$lot_id])){

            $parent_linked_iid = current($br['pare'][$ot_id][$cat_piid][$lot_id]);
        }

        // если id третьего объекта не найден
        // ищем по каталогу-паренту
        if(!$linked_iid && $parent_linked_iid){
            $linked_iid = $parent_linked_iid;
        }

        if($linked_iid){
            if($linked_iid != $exists_liid){
                $this->ah->listen('beforeComplete', 'propagandaLinkFromParent', $this, null, $_linkedOh, $linked_iid);
                $this->ah->addToLink($_linkedOh, $linked_iid);
                if($exists_liid) {
                    $this->ah->addToUnlink($_linkedOh, $exists_liid);
                }
            }
        }

        return $this->value;
    }

    /**
     * @param $_linkedOh \Verba\Model
     * @param $linkedIid
     * @throws \Exception
     */
    function propagandaLinkFromParent($_linkedOh, $linkedIid){
        if($this->action == 'new'){
            return;
        }
        $iid = $this->ah->getIID();

        $ot_id = $this->oh->getID();
        $l_ot_id = $_linkedOh->getID();
        $br = \Verba\Branch::get_branch(array($ot_id => array('aot' => $ot_id, 'iids' => $iid)), 'down', 1, false, false, 'd', false);
        if($br['handled'][$ot_id]){
            foreach($br['handled'][$ot_id] as $chiid){
                /**
                 * @var $ae \AddEdit
                 */
                $ae = $this->oh->initAddEdit(array('action' => 'edit'));
                $ae->setIID($chiid);
                $ae->addMultipleParents(array($ot_id => array($iid), $l_ot_id=>array($linkedIid)));
                $ae->addedit_object();
                if(!$ae->haveErrors()){
                    $this->log()->error('Some error with child#'.$chiid);
                }
            }
        }
    }
}
