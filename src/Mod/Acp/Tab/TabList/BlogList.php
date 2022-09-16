<?php

namespace Verba\Mod\Acp\Tab\TabList;


class BlogList extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'blog acp tab list'
  );
  public $ot = 'blog';
  public $action = 'list';
  public $url = '/acp/h/blogadmin/list';
  public $linkedTo = array('type' => 'tab', 'id' => 'CatalogAef');

  function states(){
    $r = parent::states();
    $r['editlistobject'] = array(
      'type' => 'tabset',
      'name' => 'BlogUpdate',
    );
    return $r;
  }
}
?>