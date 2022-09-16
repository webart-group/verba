<?php

namespace Verba\Mod\Acp\Tabset;

class TimecardUpdate extends \Verba\Mod\Acp\Tabset{
  public $maxLevel = 0;
  public $currentLevel = 0;

  function tabs(){
    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'updateform',
        'ot' => 'gamecard',
        'url' => '/acp/h/gamecardadmin/cuform',
        'button' => array('title' => 'gamecard acp form edit'),
      ),
      'FilekeyListByTimecard',
      'ImagesByObjectList' => array(
        'button' => array(
          'title' => 'gamecard acp list images tab'
        ),
        'url' => '/acp/h/gamecardadmin/image/list',
        'linkedTo' => array('id' => 'ListObjectForm'),
        'contentTitleSubst' => array(
          'pattern' => 'gamecard acp list images contentTitle thisByGamecard',
        ),
      ),
      'MetaAef' => array('linkedTo' => array('type' => 'tab', 'id' => 'ListObjectForm'))
    );

    //add Linked Objects Tab
    if($this->currentLevel <= $this->maxLevel){
      $tabs['LinkedTimecards'] = array(
        'linkedTo' => array('type' => 'tab', 'id' => 'ListObjectForm'),
        'ot' => 'gamecard',
        'maxLevel' => $this->maxLevel,
        'currentLevel' => $this->currentLevel,
      );
    }

    return $tabs;
  }
}
?>