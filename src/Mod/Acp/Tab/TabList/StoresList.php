<?php

namespace Verba\Mod\Acp\Tab\TabList;


class StoresList extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'store acp tabs list'
  );
  public $ot = 'store';
  public $action = 'list';
  public $url = '/acp/h/store/list';

  //function states(){
//    $r = parent::states();
//    $r['editlistobject'] = array(
//      'type' => 'tabset',
//      'name' => 'UserUpdate',
//    );
//    return $r;
//  }
}
?>