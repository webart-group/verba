<?php

namespace Verba\Mod\Acp\Node;


class catalogtimecards extends \Verba\Mod\Acp\Node{
  public $acpNodeType = 'catalogtimecards';
  public $ot = 'catalog';
  public $pot = 'catalog';
  public $titleLangKey = 'catalog acp node name';

  function tabsets(){
    return array(
      'default' => 'CatalogTimecards',
    );
  }

  function menu(){
    return array(
      'addnewnode' => false,
      'deletenode' => array(
        'title' => \Verba\Lang::get('catalog acp node menu delete'),
        'cfg' => array(
          'url_sfx' => '/catalogadmin/remove'
        )
      )
    );
  }
}
?>
