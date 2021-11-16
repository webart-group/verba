<?php
namespace Verba\Html;

class Textarea extends Element
{
  public $tag = 'textarea';
  public $cols = 40;
  public $rows = 4;
  public $readonly;

  function setCols($val){
    if(is_int($val = intval($val)) && $val > 0)
      $this->cols = $val;
  }

  function getTagName(){
    return $this->tag;
  }

  function getCols(){
    return $this->cols;
  }
  function makeColsTagAttr(){
    return is_numeric($this->cols) ? 'cols="'.intval($this->cols).'"' : '';
  }
  function setRows($val){
    if(is_int($val = intval($val)) && $val > 0)
      $this->rows = $val;
  }
  function getRows(){
    return $this->rows;
  }
  function makeRowsTagAttr(){
    return is_numeric($this->rows) ? 'rows="'.intval($this->rows).'"' : '';
  }

  function setReadonly($val){
    $this->readonly = (bool) $val;
  }
  function getReadonly(){
    return $this->readonly;
  }
  function makeReadonly(){
    return $this->readonly ? 'readonly="readonly"' : '';
  }

  function prepareEAttrs(){

    $ia = parent::prepareEAttrs();
    $ia['cols'] = $this->makeColsTagAttr();
    $ia['rows'] = $this->makeRowsTagAttr();

    return $ia;
  }

  function makeE(){
    $this->fire('makeE');
    $tag = $this->getTag();

    $this->setE('<'
      . $tag
      . $this->prepareEAttrsImploded()
      .'>'
      .htmlspecialchars($this->getValue(), ENT_QUOTES, 'utf-8')
      .'</'.$tag.'>');
    $this->fire('makeEFinalize');
  }

  function exportAsCfg(){
    $r = parent::exportAsCfg();
    $r['cols'] = $this->getCols();
    $r['rows'] = $this->getRows();
    if($this->getReadonly() !== null) $r['readonly'] = $this->getReadonly();

    return $r;
  }
}
