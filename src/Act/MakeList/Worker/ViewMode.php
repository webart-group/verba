<?php
namespace Verba\Act\MakeList\Worker;

use \Verba\Act\MakeList\Worker;

class ViewMode extends Worker{

  public $jsScriptFile = 'ViewMode';

  public $cookieName = '';
  public $globalKey = 'unnamed';
  public $defaultStyle = 'view-list';

  function init(){
    $this->cookieName = 'view-mode-'.(string)$this->globalKey;
    $this->parent->listen('beforeParse', 'run', $this, 'ViewMode_run');
  }

  function run(){
    $list_view_class = isset($_COOKIE[$this->cookieName])
    && !empty($_COOKIE[$this->cookieName])
      ? $_COOKIE[$this->cookieName]
      : $this->defaultStyle;
    $class = $this->parent->gC('list_box class');
    $this->parent->sC($class.' '.$list_view_class, 'list_box class');
  }

}
