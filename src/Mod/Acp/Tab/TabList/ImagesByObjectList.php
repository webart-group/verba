<?php

namespace Verba\Mod\Acp\Tab\TabList;


class ImagesByObjectList extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'image acp tab byObject'
  );
  public $ot = 'image';
  public $action = '';
  public $linkedTo = array('type' => 'tab');
  public $url = '/acp/h/objectadmin/image/list';

  function states(){
    $r = parent::states();
    //$r['editlistobject'] = array(
//        'type' => 'tabset',
//        'name' => 'BlockUpdate',
//    );
    return $r;
  }
}
?>
