<?php

namespace Verba\Mod\Acp\Tab\TabList;


class LinkedTextblocks extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'textblock acp list title'
  );
  public $ot = 'textblock';
  public $action = 'list';
  public $url = '/acp/h/textblock/list';
  public $linkedTo = array('type' => 'tab', 'id' => 'ListObjectForm');
}
?>