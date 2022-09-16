<?php

namespace Verba\Mod\Acp\Node;



class textblocks extends \Verba\Mod\Acp\Node
{
    public $acpNodeType = 'textblocks';
    public $titleLangKey = 'textblock acp node';

    function tabsets()
    {
        return array(
            'default' => 'Textblocks',
        );
    }
}

