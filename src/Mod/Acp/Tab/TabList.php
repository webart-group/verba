<?php

namespace Verba\Mod\Acp\Tab;


use Verba\Mod\Acp\Tab;

class TabList extends Tab
{
    public $viewName = 'List';
    public $action = 'list';

    function states()
    {
        $r = [
            'addlistobject' => [
                'type' => 'tabset',
                'name' => 'ListAEForm',
                'cfg' => [
                    'tabs' => [
                        'ListObjectForm' => [
                            'action' => 'createform',
                            'button' => [
                                'title' => 'acp list tabs addobject'
                            ]
                        ]
                    ],
                ],
            ],
            'editlistobject' => [
                'type' => 'tabset',
                'name' => 'ListAEForm',
                'cfg' => [
                    'tabs' => [
                        'ListObjectForm' => [
                            'action' => 'updateform',
                            'button' => [
                                'title' => 'acp list tabs editobject'
                            ]
                        ]
                    ],
                ],
            ]
        ];
        return $r;
    }
}

//
//$r = array(
//  'addlistobject' => array(
//    'type' => 'tabset',
//    'name' => 'ListAEForm',
//    'tabs' => array(
//      'ListObjectForm' => array(
//        'action' => 'createform',
//        'button' => array(
//          'title' => 'acp list tabs addobject'
//        )
//      )
//    ),
//  ),
//  'editlistobject' => array(
//    'type' => 'tabset',
//    'name' => 'ListAEForm',
//    'tabs' => array(
//      'ListObjectForm' => array(
//        'action' => 'updateform',
//        'button' => array(
//          'title' => 'acp list tabs editobject'
//        )
//      )
//    ),
//  )
//);
