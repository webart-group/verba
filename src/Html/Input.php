<?php
namespace Verba\Html;

class Input extends Element{
  public $tag = 'input';
  public $type = '';

  function getType(){
    return $this->type;
  }

  function getTagName(){
    return $this->tag;
  }

  function makeTypeTagAttr(){
    return $this->type ? 'type="'.$this->type.'"' : '';
  }

  function exportAsCfg(){
    $r = parent::exportAsCfg();
    $r['type'] = $this->getType();
    $r['readonly'] = $this->getReadonly();
    return $r;
  }
  function prepareEAttrs(){
    $ia = array(
      'type' => $this->makeTypeTagAttr()
    );
    $ia += parent::prepareEAttrs();
    return $ia;
  }
}