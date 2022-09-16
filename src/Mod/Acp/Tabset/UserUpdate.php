<?php

namespace Verba\Mod\Acp\Tabset;

class UserUpdate extends \Verba\Mod\Acp\Tabset{
  function tabs(){
    $tabs = array(
      'ListObjectForm' => array(
        'action' => 'updateform',
        'ot' => 'user',
        'url' => '/acp/h/user/cuform',
        'button' => array('title' => 'user acp form update title'),
      ),
      'UserStore' => array(
        'class' => 'ACPTab_List',
        'linkedTo' => array('type' => 'tab', 'id' => 'ListObjectForm'),
        'url' => '/acp/h/store/list',
        'button' => array('title' => 'user acp form update shopTitle'),
        'ot' => 'store',
        'action' => 'list',
      ),
      'UserAccounts' => array(
        'class' => 'ACPTab_List',
        'linkedTo' => array('type' => 'tab', 'id' => 'ListObjectForm'),
        'url' => '/acp/h/account/list',
        'button' => array('title' => 'user acp tabs accounts'),
        'ot' => 'account',
        'action' => 'list',
      ),
      'UserPrequisites' => array(
        'class' => 'ACPTab_List',
        'linkedTo' => array('type' => 'tab', 'id' => 'ListObjectForm'),
        'url' => '/acp/h/prequisite/list',
        'button' => array('title' => 'user acp tabs prequisites'),
        'ot' => 'prequisite',
        'action' => 'list',
      )

    );
    return $tabs;
  }
}
