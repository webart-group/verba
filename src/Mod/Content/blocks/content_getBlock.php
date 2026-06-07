<?php

use webart\verba\src\Mod\Textblock\Block\textblock_getBlock;

class content_getBlock extends textblock_getBlock {

  protected $_mod = 'content';
  protected $_ot = 'content';

  public $templates = array(
    'content' => '/content/block.tpl',
    'title' => '/content/title.tpl'
  );

}
