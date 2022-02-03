<?php

namespace Mod\Sitemap\Block;

use Mod\Sitemap\ContextFile;

interface generator_int{
  function getContext();
}

class Generator extends \Block\Template implements generator_int{

  protected $context;

  public $templates = array(
    'url' => 'sitemap/url.tpl'
  );

  /**
   * @return ContextFile
   */

  function getContext(){
    if($this->context === null){
      $this->context = false;
      $p = $this->getParent();
      if($p instanceof generator_int ){
        $this->context = $p->getContext();
      }
    }
    return $this->context;
  }

}