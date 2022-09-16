<?php

namespace Verba\Mod\Acp\Tabset;


class NodeCreateCategory extends \Verba\Mod\Acp\Tabset
{
    function tabs()
    {
        return array(
            'CategoryAef' => array(
                'action' => 'createform',
                'linkedTo' => array('type' => 'node'),
                'iid' => false,
                'instanceOf' => false,
            ),
        );
    }
}
