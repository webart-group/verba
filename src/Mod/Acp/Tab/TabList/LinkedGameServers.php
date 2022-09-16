<?php

namespace Verba\Mod\Acp\Tab\TabList;


class LinkedGameServers extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'game acp servers tab'
  );
  public $ot = 'game_server';
  public $action = 'list';
  public $url = '/acp/h/game_server/list';
  public $linkedTo = array('type' => 'tab', 'id' => 'ListObjectForm');
}
?>