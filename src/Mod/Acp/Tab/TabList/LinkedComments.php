<?php

namespace Verba\Mod\Acp\Tab\TabList;


class LinkedComments extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'comment acp list title'
  );
  public $ot = 'comment';
  public $action = '';
  public $url = '/acp/h/comment/list';
  public $linkedTo = array('type' => 'tab', 'id' => 'ListObjectForm');
}
?>