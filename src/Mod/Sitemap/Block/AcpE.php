<?php

namespace Verba\Mod\Sitemap\Block;

class AcpE extends \page_eInteractive{

  /**
   * @var $eid string ID элемента
   */
  public $eid = 'acp_tools_sitemap';
  public $component = 'Sitemap';
  public $script = 'acp/tools/sitemap.js';
  public $style = 'acp/tools/sitemap.css';
  public $classes = 'acp-tool-sitemap';
  public $group = 'acp-sitemap-tool';

  function init(){

    $this->tpl->define(array(
      'ui' => '/sitemap/acp/tools/generate.tpl'
    ));

  }
}