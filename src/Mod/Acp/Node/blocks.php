<?php

namespace Verba\Mod\Acp\Node;


class blocks extends \Verba\Mod\Acp\Node
{
    public $acpNodeType = 'blocks';
    public $ot = 'block';

    function tabsets()
    {
        return array(
            'default' => 'Blocks',
        );
    }

    function menu()
    {
        return;
    }
}
