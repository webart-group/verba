<?php

namespace Verba\Mod\Acp\Tab;


class LinkedTimecards extends TabList{
  public $button = array(
    'title' => 'gamecard acp list linked tab'
  );
  public $ot = 'gamecard';
  public $action = '';
  public $url = '/acp/h/gamecardadmin/gamecard/list';
  public $linkedTo = array('type' => 'tab', 'id' => 'ListObjectForm');
  public $maxLevel = 1;
  public $currentLevel = 0;

  function __construct($cfg = null){
    parent::__construct($cfg);
  }

  function states(){
    $r = parent::states();
    $r['editlistobject'] = array(
      'type' => 'tabset',
      'name' => 'TimecardUpdate',
      'cfg' => array(
        'maxLevel' => $this->maxLevel,
        'currentLevel' => ++$this->currentLevel,
      )
    );
    return $r;
  }
}
?>