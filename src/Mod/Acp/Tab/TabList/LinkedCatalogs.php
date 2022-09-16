<?php

namespace Verba\Mod\Acp\Tab\TabList;


class LinkedCatalogs extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'catalog acp linked tab list'
  );
  public $ot = 'catalog';
  public $action = '';
  public $url = '/acp/h/catalogadmin/linking/list';
  public $linkedTo = array('type' => 'tab', 'id' => 'ListObjectForm');
  public $contentTitleSubst = array(
    'pattern' => 'products acp list linked contentTitle allCatalogs',
  );
  function states(){
    $r = parent::states();
    $r['editlistobject'] = false;
    return $r;
  }
}
?>