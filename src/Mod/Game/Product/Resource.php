<?php
namespace Mod\Game\Product;

class Resource extends Bid {

  protected $cartItemClassName = '\\Mod\\Cart\\Item\\Resource';

  function sold($items, $ah){

    // Предполагается, что для товаров типа Ресурс купленное количество ресурса
    // хранится в поле $amount

    foreach($items as $ihash => $idata){
      if(!isset($idata['_extra']['tform']['amount'])
      || !($amount = reductionToFloat($idata['_extra']['tform']['amount']))
      ){
        return false;
      }

      $_prod = \Verba\_oh($idata['ch_ot_id']);
      $ae = $_prod->initAddEdit(array('action' => 'edit'));
      $ae->setIid($idata['ch_iid']);
      $ae->setGettedObjectData(array('quantityAvaible' => '-'.$amount));
      $ae->setDelegatedOwnerId(SYS_USER_ID);
      $ae->addedit_object();
    }

    return true;
  }

  function sellCanceled($items, $ah){

    // Предполагается, что для товаров типа Ресурс купленное количество ресурса
    // хранится в поле $amount

    foreach($items as $ihash => $idata){
      if(!isset($idata['_extra']['tform']['amount'])
        || !($amount = reductionToFloat($idata['_extra']['tform']['amount']))
      ){
        return false;
      }

      $_prod = \Verba\_oh($idata['ch_ot_id']);
      $ae = $_prod->initAddEdit(array('action' => 'edit'));
      $ae->setIid($idata['ch_iid']);
      $ae->setGettedObjectData(array('quantityAvaible' => '+'.$amount));
      $ae->setDelegatedOwnerId(SYS_USER_ID);
      $ae->addedit_object();
    }

    return true;
  }

}