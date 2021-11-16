<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use Act\AddEdit\Handler\Around;

class Promotions extends Around
{
    function run()
    {
        if($this->action == 'edit'){
            return;
        }
        return $this->value;
        //$items = $this->getExtendedData('items');
//    if(!$items && !is_array($items)){
//      return false;
//    }
//    $iids = array();
//    $pdata = array();
//    foreach($items as $hash => $item){
//      if($items instanceof \Verba\Mod\Cart\Item){
//        continue;
//      }
//      $iids[$item->id] = $item->parentId ? $item->parentId : $item->id;
//      $pdata[$item->id] = $item->title;
//    }
//    $_promo = \Verba\_oh('promotion');
//    $_product = \Verba\_oh('product');
//    $q = "SELECT
//l.ch_iid as prid,
//a.id,
//a.title_ru,
//a.annotation_ru
//FROM ".$_promo->vltURI($_product)." l
//LEFT JOIN ".$_promo->vltURI()." a
//ON a.id = l.p_iid && a.active = 1
//WHERE l.p_ot_id = '".$_promo->getID()."' && l.`ch_iid` IN('".implode("', '",array_unique($iids))."')
//";
//    $sqlr = $this->DB()->query($q);
//    $promos = array();
//    $prid_promos = array();
//    if($sqlr && $sqlr->getNumRows()){
//      while($row = $sqlr->fetchRow()){
//        $promos[$row['id']] = $row;
//        if(!isset($prid_promos[$row['prid']])){
//          $prid_promos[$row['prid']] = array();
//        }
//        $prid_promos[$row['prid']][] = $row['id'];
//      }
//    }else{
//      return '';
//    }
//    $r = array();
//    foreach($iids as $iid => $prid){
//      if(!isset($prid_promos[$prid])){
//        continue;
//      }
//      $str = $pdata[$iid];
//      foreach($prid_promos[$prid] as $promo_id){
//        if(!isset($r[$iid])){
//          $r[$iid] = array();
//        }
//        $r[$iid][$promo_id] = !empty($promos[$promo_id]['annotation_ru'])
//          ? $promos[$promo_id]['annotation_ru']
//          : $promos[$promo_id]['title_ru'];
//      }

//    }

//    return serialize($r);
    }
}
