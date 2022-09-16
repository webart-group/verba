<?php

namespace Verba\Mod\Acp\Tabset;

namespace Verba\Mod\Acp\Tabset;

class FilekeyUpdate extends \Verba\Mod\Acp\Tabset
{
    function tabs()
    {
        $tabs = array(
            'ListObjectForm' => array(
                'action' => '',
                'ot' => 'filekey',
                'url' => '/acp/h/gamecardadmin/filekey/cuform',
                'button' => array('title' => 'gamecard acp filekey form edit'),
            ),
        );
        return $tabs;
    }
}
