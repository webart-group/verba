<?php
namespace Verba\Act\MakeList\Worker;

use \Verba\Act\MakeList\Worker;

class ItemClickToLoadUrl extends Worker{

  public $jsScriptFile = 'ItemClickToLoadUrl';

  public $urlGenerator = '\Mod\Seo::idToSeoStr';

  function init(){
    $this->parent->listen('rowBefore', 'genUrl', $this, 'ItemClickToLoadUrl_genUrl');
  }

  function genUrl(){

    $args = array(
      $this->parent->row,
      array(
        'seq' => $this->parent->getCurrentPos(),
        'slID' => $this->parent->getID()
      )
    );

    if(isset($this->urlGenerator[0])
      && is_string($this->urlGenerator[0])
      && \Verba\Hive::isModExists($this->urlGenerator[0]))
    {
      $this->urlGenerator[0] = \Verba\_mod($this->urlGenerator[0]);
    }

    $url = call_user_func_array($this->urlGenerator, $args);

    $this->parent->rowClass[] = 'clickable';
    $this->parent->rowAttrs['item-go-url'] = $url;
  }
}
