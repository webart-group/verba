<?php

namespace Verba\Mod\Acp\Tab\TabList;


class LinkedProducts extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'products acp list linked tab'
  );
  public $ot = 'product';
  public $action = '';
  public $url = '/acp/h/productadmin/linking/list';
  public $linkedTo = array('type' => 'tab', 'id' => 'ListObjectForm');
  public $maxLevel = 1;
  public $currentLevel = 0;
  public $contentTitleSubst = array(
    'pattern' => 'products acp list linked contentTitle byProduct',
  );
  function states(){
    $r = parent::states();
    $r['editlistobject'] = false;
    return $r;
  }
}
?>