<?php

namespace Verba\Mod\Acp\Node;


class bannersgroup extends \Verba\Mod\Acp\Node
{
    public $acpNodeType = 'bannersgroup';

    public $titleLangKey;

    function tabsets()
    {
        return [
            'default' => ['class' => 'BannersGroup'],
        ];
    }
}
