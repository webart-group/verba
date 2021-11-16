<?php
namespace Verba\Act\AddEdit\Handler\After;

use Act\AddEdit\Handler\After;

class GameBidHandler extends After{

  protected $prodOtId;
  protected $prodId;
  /**
   * @var \Verba\Model\Item
   */
  protected $prodItem;
  protected $storeId;

  public $attrs = array(
    'active',
    'owner',
    'price',
    'picture',
    'gameCatId',
    'serviceCatId',
    'currencyId',
  );

  function run(){

    $_bid = \Verba\_oh('bid');
    $b_ot_id = $_bid->getID();
    $this->prodOtId = $this->ah->oh()->getID();
    $this->prodId = $this->ah->getIID();

    $this->prodItem = $this->ah->getActualItem();

    if(!$this->prodItem){
      $this->log()->error('Unable to get Source entry data. Bid creation cenceled');
      return false;
    }
    $this->storeId = $this->prodItem->getNatural('storeId');

    $bidData = $this->genBidFieldsByGameProd();

    $bidData = array_replace_recursive($bidData, array(
      'storeId' => $this->storeId,
      'prodOtId' => $this->prodOtId,
      'prodId' => $this->prodId,
    ));

    $action = $this->ah->getAction();
    if($action == 'edit'){
      // ищем бид у которого парент- текущий лот
      $br = \Verba\Branch::get_branch(array($this->prodOtId => array(
        'aots' => $b_ot_id,
        'iids' => $this->prodId
      )), 'down', 1,false,false,null,false);
      if(!isset($br['handled'][$b_ot_id]) || empty($br['handled'][$b_ot_id])){
        $action = 'new';
      }else{
        $bid_iid = current($br['handled'][$b_ot_id]);
      }
    }

    $aeBid = $_bid->initAddEdit([
      'action' => $action,
    ]);
    if($action == 'edit'){
      $aeBid->setIid($bid_iid);
    }

    $aeBid->setGettedObjectData($bidData);
    $iid = $aeBid->addedit_object();
    if(!$iid || $aeBid->haveErrors()){
      $this->log()->error('Ошибка обновления Бида');
      return false;
    }

    return true;
  }

  function genBidFieldsByGameProd(){

    $_prod = $this->prodItem->oh();
    $r = array();
    foreach($this->attrs as $acode){
      $A = $_prod->A($acode);

      if($A->isPredefined()){
        $r[$acode] = $this->prodItem->getNatural($acode);
      }else{
        if($A->isLcd()){
          $r[$acode] = array();
          foreach(\Lang::getUsedLC() as $clang){
            $r[$acode][$clang] = $this->prodItem->getNatural($acode, $clang);
          }
        }else{
          $r[$acode] = $this->prodItem->getNatural($acode);
        }
      }
    }

    $r['title'] = $this->extractTitle();
    $r['url'] = \Verba\_mod('offer')->getOfferUrl($this->prodItem);

    return $r;
  }

  function extractTitle(){

    $all_lc = \Verba\Lang::getUsedLC();
    $r = array_fill_keys($all_lc, '');
    $genFields = [];

    $bidTitleFields = $this->prodItem->oh()->p('bidTitleFields');

    if(is_string($bidTitleFields) && !empty($bidTitleFields)){
      $genFields = explode(',',$bidTitleFields);

    }else{

      if($this->prodItem->getNatural('title')) {
        $genFields = ['title'];
      }elseif($this->prodItem->getNatural('annotation')){
        $genFields = ['annotation'];
      }

    }

    if(!count($genFields)){
      return $r;
    }

    $vs = array_fill_keys($all_lc, array());
    foreach($all_lc as $clc){

      $this->prodItem->setInternalLang($clc);
      foreach($genFields as $fcode){
        $v = $this->prodItem->getValue($fcode);
        if(empty($v)){
          continue;
        }
        $vs[$clc][] = $v;
      }
      if(count($vs[$clc])){
        $r[$clc] = implode(', ', $vs[$clc]);
      }
    }

    return $r;
  }
}
