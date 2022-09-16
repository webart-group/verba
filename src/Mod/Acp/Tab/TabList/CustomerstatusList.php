<?php

namespace Verba\Mod\Acp\Tab\TabList;


class CustomerstatusList extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'customer status acp tab list'
  );
  public $ot = 'customerstatus';
  public $action = 'list';
  public $url = '/acp/h/customeradmin/status/list';
}
?>