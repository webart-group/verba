<?php

namespace Verba\Mod\Acp\Tab\TabList;


class ContentList extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'content acp tab pages'
  );
  public $ot = 'content';
  public $action = 'list';
  public $linkedTo = array('type' => 'tab');
  public $url = '/acp/h/content/list';
}
?>