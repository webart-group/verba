<?php

namespace Verba\Mod\Acp\Tab\TabList;


class ImagesByProjectList extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'projects acp tab images'
  );
  public $ot = 'image';
  public $action = '';
  public $linkedTo = array('type' => 'tab');
  public $url = '/acp/h/projectsadmin/image/list';

  function states(){
    $r = parent::states();
    $r['editlistobject'] = array(
        'type' => 'tabset',
        'name' => 'BlockUpdate',
    );
    return $r;
  }
}
?>