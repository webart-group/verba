<?php
namespace Verba\Html;

class Text extends Input
{
  public $type = 'text';
  public $size;
  public $maxlength;

  function setSize($var){
    if(($var = intval($var)) && $var > 0){
      $this->size =  $var;
    }
  }
  function getSize(){
    return $this->size;
  }
  function makeSizeTagAttr(){
    return is_int($this->size) ? "size=\"{$this->size}\"" : '';
  }

  function setMaxlength($val){
    if(is_int($val = intval($val)) && $val > 0){
      $this->maxlength = $val;
    }
  }
  function getMaxlength(){
    return $this->maxlength;
  }
  function makeMaxlengthTagAttr(){
    return is_int($this->getMaxlength()) ? "maxlength=\"{$this->getMaxlength()}\"" : '';
  }

  function prepareEAttrs(){

    $ia = parent::prepareEAttrs();
    $ia['size'] = $this->makeSizeTagAttr();
    $ia['maxLength'] = $this->makeMaxlengthTagAttr();

    return $ia;
  }

  function makeE(){
    $this->fire('makeE');
    $tag = $this->getTag();

    $this->setE('<'
      . $tag
      . ' value="'.htmlspecialchars($this->getValue(), ENT_QUOTES, 'utf-8')
      . '"'.$this->prepareEAttrsImploded().'/>'
    );
    $this->fire('makeEFinalize');
  }

  function exportAsCfg(){
    $r = parent::exportAsCfg();
    if($this->getSize() !== null ) $r['size'] = $this->getSize();
    if($this->getMaxlength() !== null ) $r['maxlength'] = $this->getMaxlength();
    return $r;
  }
}
