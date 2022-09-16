<?php

namespace Verba\Mod\Acp\Tabset;

class PdSetUpdate extends \Verba\Mod\Acp\Tabset
{
    function tabs()
    {
        $tabs = [
            'ListObjectForm' => array(
                'action' => 'updateform',
                'ot' => 'pd_set',
                'url' => '/acp/h/pd_set/cuform',
                'button' => array('title' => 'pd_set form update'),
            ),
            'LinkedObjects' => array(
                'ot' => 'predefined',
                'url' => '/acp/h/predefined/list',
                'button' => array('title' => 'opredefined tab list'),
            ),
        ];

        return $tabs;
    }

}
