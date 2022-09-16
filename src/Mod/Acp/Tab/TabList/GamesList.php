<?php

namespace Verba\Mod\Acp\Tab\TabList;


class GamesList extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'game acp tabs list'
  );
  public $ot = 'game';
  public $action = 'list';
  public $url = '/acp/h/game/list';

  function states(){
    $r = parent::states();
    $r['editlistobject'] = array(
      'type' => 'tabset',
      'name' => 'GameUpdate',
    );
    return $r;
  }

}
?>