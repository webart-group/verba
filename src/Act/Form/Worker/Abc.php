<?php
namespace Verba\Act\Form\Worker;

use \Verba\Act\Form\Worker;

class Abc extends Worker{


  function init(){

    if($this->overwriteTabViewStates == false){
      return;
    }

    $this->list->listen('queryExecuted', 'fillListStates', $this);
  }

  function fillListStates(){

    if(!array_key_exists('addlistobject',$this->viewStates)) {

      $this->viewStates['addlistobject'] =

        \Verba\Mod\Acp\Tabset::createTabsetByName('ListAEForm', array(
          'tabs' => array(
            'ListObjectForm' => array(
              'action' => 'createform',
              'button' => array(
                'title' => 'acp list tabs addobject'
              )
            )
          ),
        ));
    }
    if(!array_key_exists('editlistobject',$this->viewStates)) {

      $this->viewStates['editlistobject'] = \Verba\Mod\Acp\Tabset::createTabsetByName('ListAEForm',array(
        'tabs' => array(
          'ListObjectForm' => array(
            'action' => 'updateform',
            'button' => array(
              'title' => 'acp list tabs editobject'
            )
          )
        ),
      ));

    }
  }
}
