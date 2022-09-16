<?php

namespace Verba\Mod\Acp\Tab;


class FilekeyListByTimecard extends TabList{
  public $button = array(
    'title' => 'gamecard acp list filekey tab'
  );
  public $ot = 'filekey';
  public $action = '';
  public $url = '/acp/h/gamecardadmin/filekey/list';
  public $linkedTo = array('type' => 'tab', 'id' => 'ListObjectForm');

  function __construct($cfg = null){
    parent::__construct($cfg);
  }

  function states(){
    $r = parent::states();
    $r['editlistobject'] = array(
      'type' => 'tabset',
      'name' => 'FilekeyUpdate',
    );
    return $r;
  }
}
?>