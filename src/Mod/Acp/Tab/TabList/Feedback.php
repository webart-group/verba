<?php

namespace Verba\Mod\Acp\Tab\TabList;


class Feedback extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'feedback acp tab list'
  );
  public $ot = 'feedback';
  public $action = 'list';
  public $url = '/acp/h/feedback/list';
}
?>