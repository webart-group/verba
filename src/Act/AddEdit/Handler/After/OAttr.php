<?php
namespace Verba\Act\AddEdit\Handler\After;

use Act\AddEdit\Handler\After;

class OAttr extends After{

  function run(){
    $r = false;

    if($this->ah->getAction() == 'new'){
      $r = $this->create();
    }elseif($this->ah->getAction() == 'edit'){
      $r = $this->update();
    }

    \Verba\_mod('system')->planeClearCache();

    return $r;
  }

  function update(){

//    $attr_id = $this->ah->getIID();
//    $_attr = \Verba\_oh('ot_attribute');
//    $oh = \Verba\_oh($this->ah->getExistsValue('ot_iid'));

  }

  function create(){
    $attr_id = $this->ah->getIID();
    $_attr = \Verba\_oh('ot_attribute');
    $attr = $_attr->getData($attr_id, 1);
    $oh = \Verba\_oh($attr['ot_iid']);
    $createIndex = $this->ah->getGettedValue('_db_index');
    $modOtype = \Mod\Otype::getInstance();
    if(!is_array($columnMetaData = $modOtype->addTableFieldForAttribute($oh, $attr, $createIndex))){
      $this->log()->error('Unable to create Table Column for ot: '.var_dump($oh->getCode()).', attr: '.var_dump($attr['attr_code']));
      return false;
    }

    if(is_array($columnMetaData['ah']) && !empty($columnMetaData['ah'])){
      $_ah = \Verba\_oh('ah');
      $ahs_iids = array();
      foreach($columnMetaData['ah'] as $ah_id => $ah_code){
        $ahs_iids[] = $ah_id;
      }
      $lr = $_attr->link($attr_id, array($_ah->getID() => $ahs_iids));
    }

    return true;
  }

}
?>
