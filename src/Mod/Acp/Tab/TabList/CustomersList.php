<?php

namespace Verba\Mod\Acp\Tab\TabList;


class CustomersList extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'customer acp tab list'
  );
  public $ot = 'customer';
  public $action = 'list';
  public $url = '/acp/h/customer/list';
}
?>