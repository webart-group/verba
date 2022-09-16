<?php

namespace Verba\Mod\Acp\Tabset;

class MusiccdUpdate extends \Verba\Mod\Acp\Tabset{
  public $isParent = 0;

  function tabs(){

    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'updateform',
        'url' => '/acp/h/musiccd/cuform',
        'button' => array('title' => 'musiccd acp form edit'),
      ),
//      'ImagesByObjectList' => array(
//        'button' => array(
//          'title' => 'image acp tab byObject'
//        ),
//        'url' => '/acp/h/productadmin/image/list',
//        'linkedTo' => array('id' => 'ListObjectForm'),
//        'contentTitleSubst' => array(
//          'pattern' => 'products acp contentTitle imagesByProduct',
//        ),
//      ),
    );
    //add Variants Tab if current level is lowest than maxLevel
    // (primary product)
    if($this->isParent){
      $listTabCfg = array(
        'linkedTo' => array('type' => 'tab', 'id' => 'ListObjectForm'),
        'maxLevel' => $this->maxLevel,
        'currentLevel' => $this->currentLevel,
        'contentTitleSubst' => array(
          'pattern' => 'musiccd acp contentTitle variants',
        ),
        'url' => '/acp/h/musiccd/variants',
        'action' => 'variants',
      );
      //product variants tab
      if($this->currentLevel > 0){
        $listTabCfg['button']['title'] = 'musiccd acp tab variants';
      }
      $tabs['ProductsList'] = $listTabCfg;
      //rvideo tab
//      $tabs['RvideoList'] = array(
//        'linkedTo' => array('type' => 'tab', 'id' => 'ListObjectForm'),
//      );
      //linked comments tab

//      $tabs['LinkedCatalogs'] = array(
//        'url' => '/acp/h/musiccd/catalog/list'
//      );
    }
    $tabs['LinkedComments'] = array(
//        'linkedTo' => array('type' => 'tab', 'id' => 'ListObjectForm'),
//        'ot' => 'product',
      'maxLevel' => $this->maxLevel,
      'currentLevel' => $this->currentLevel,
    );
    //meta data tab
    $tabs['MetaAef'] = array('linkedTo' => array('id' => 'ListObjectForm'));

    return $tabs;
  }
}
?>