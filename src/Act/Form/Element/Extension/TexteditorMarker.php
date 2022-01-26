<?php

namespace Verba\Act\Form\Element\Extension;

use \Verba\Act\Form\Element\Extension;

class TexteditorMarker extends Extension
{
  public $marker = '#!#';
  public $templates = array(
    'fe_body' => 'aef/fe/fckeditor/fckeditor_marker.tpl',
    'marker' => 'aef/fe/fckeditor/marker_help.tpl'
  );

  function engage(){
    $this->fe->listen('makeE', 'addMarkerHelp', $this);
  }

  function setMarker($val){
    if(is_string($val) && !empty($val)) $this->marker = $val;
  }
  function getMarker(){
    return $this->marker;
  }

  function addMarkerHelp(){
    if(!is_string($this->marker) || empty($this->marker)) return false;

    $this->tpl->define('marker_help', $this->templates['marker']);
    $this->fe()->templates['body'] = $this->templates['fe_body'];
    $this->tpl->assign('MARKER', $this->getMarker());
    $this->tpl->parse('MARKER_HELP','marker_help');
  }
}
