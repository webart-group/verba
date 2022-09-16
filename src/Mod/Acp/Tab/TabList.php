<?php

namespace Verba\Mod\Acp\Tab;


class TabList extends \Verba\Mod\Acp\Tab{

  public $viewName = 'List';
  public $action = 'list';

  function states(){
    $r = array(
      'addlistobject' => array(
        'type' => 'tabset',
        'name' => 'ListAEForm',
        'cfg' => array(
          'tabs' => array(
            'ListObjectForm' => array(
              'action' => 'createform',
              'button' => array(
                'title' => 'acp list tabs addobject'
              )
            )
          ),
        ),
      ),
      'editlistobject' => array(
        'type' => 'tabset',
        'name' => 'ListAEForm',
        'cfg' => array(
          'tabs' => array(
            'ListObjectForm' => array(
              'action' => 'updateform',
              'button' => array(
                'title' => 'acp list tabs editobject'
              )
            )
          ),
        ),
      )
    );
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

?>