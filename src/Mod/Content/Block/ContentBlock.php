<?php

namespace Verba\Mod\Content\Block;

use Verba\Mod\Textblock\Block\TextBlock;

class ContentBlock extends TextBlock
{

    protected $_mod = 'content';
    protected $_ot = 'content';

    public $templates = array(
        'content' => '/content/block.tpl',
        'title' => '/content/title.tpl'
    );

}
