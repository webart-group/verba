<?php

namespace Verba\Mod\Acp\Tab\TabList;


class ShopCurrencies extends \Verba\Mod\Acp\Tab\TabList{
  public $button = array(
    'title' => 'currency acp tab currencies'
  );
  public $ot = 'currency';
  public $action = 'list';
  public $url = '/acp/h/currency/list';

}
?>