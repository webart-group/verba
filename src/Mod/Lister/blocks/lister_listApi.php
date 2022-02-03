<?php
class lister_listApi extends \Verba\Block\Html{

  /**
   * @var \Model
   */

  public $oh;
  public $urlBase;

  protected $url;

  function init(){

    if(!is_string($this->urlBase) && is_string($this->url)){
      $this->urlBase = $this->url;
      $this->url = null;
    }

    if(!is_array($this->url)){
      $this->url = MakeList::$_config_default['url'];
    }

  }
}
?>