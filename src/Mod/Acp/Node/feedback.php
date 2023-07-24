<?php

namespace Verba\Mod\Acp\Node;


class feedback extends \Verba\Mod\Acp\Node
{
    public $acpNodeType = 'feedback';
    public $titleLangKey = 'feedback acp node name';

    function tabsets()
    {
        return [
            'default' => [
                'class' => 'Feedback'
            ],
        ];
    }

    function menu()
    {
        return [];
    }
}
