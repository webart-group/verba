<?php

namespace Verba\Mod\Acp\Tab\TabList;


class BannersList extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'banner acp tab list'
  );
  public $ot = 'banner';
  public $action = 'list';
  public $url = '/acp/h/banner/list';
  public $linkedTo = array('type' => 'node');

  function states(){
    $r = parent::states();
    $r['editlistobject'] = array(
      'type' => 'tabset',
      'name' => 'BannerUpdate',
    );
    return $r;
  }
}
?>