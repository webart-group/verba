<?php

namespace Verba\Mod\Acp\Tab\TabList;



class TextBlocks extends \Verba\Mod\Acp\Tab\TabList
{
    public $button = array(
        'title' => 'textblock acp tabs list'
    );
    public $ot = 'textblock';
    public $action = 'list';
    public $linkedTo = array('type' => 'node');
    public $url = '/acp/h/textblock/list';
}
