<?php
namespace Verba\Html;

class Select extends Element
{
  public $tag = 'select';
  public $multiple;
  public $size;
  public $options;
  public $values;
  /**
   * @var null|array Must be an array of view array(0 => 'value', 1 => 'text') or null if unset
   */
  protected $blank_option;
  public $extOptionAttrs = array();

  function makeOptions(){
    $this->fire('getOptions');
    if(is_string($this->options)){
      goto returnr;
    }

    $this->fire('loadValuesBefore');

    // Если значения не сформированы в результате броска события,
    // запускаем метод лоад
    if(!is_array($this->values)){
      $this->setValues($this->loadValues());
    }

    $this->fire('loadValuesAfter');

    $this->fire('parseOptions');
    if($this->options === null) {

      // Добавление в список значений Пустого варианта если применимо
      if(is_array($this->blank_option) && count($this->blank_option)){
        $this->prependValue($this->blank_option[0], $this->blank_option[1]);
      }

      // Парсим значения
      $this->options = $this->parseOptions();
    }
    $this->fire('parseOptionsFinalize');

    returnr:
    $this->fire('getOptionsFinalize');
    return $this->options;
  }

  function loadValues(){
    return  array();
  }

  function parseOptions(){

    $vls = $this->getValues();
    if(!is_array($vls) || !count($vls)) {
      return '';
    }

    $r = '';
    foreach($vls as $k => $v){
      if(is_string($v)){
        $r .= $this->generateOption($v, $k, $this->value, $this->extOptionAttrs);
      }elseif(is_object($v) && $v instanceof \Html\SelectOptgroup){
        $r .= $v->build();
      }
    }

    return $r;
  }

  static function generateOptions($data_array, $selected = false, $add_blank_option = false, $extOptionAttrs = false){
    if (!is_array($data_array))
      return false;

    $add_blank_option = (bool) $add_blank_option;

    if($add_blank_option === true)
      $data_array = array_merge(array(''=>''),$data_array);

    array_walk($data_array, array(self,'generateOptionsWalk'), $selected, $extOptionAttrs);
    return implode("\n",$data_array);
  }
  static function generateOptionsWalk(&$value, $id, $selected, $extOptionAttrs = false){
    if(!is_array($extOptionAttrs)
      || !array_key_exists($id, $extOptionAttrs)){
      $attrs = array();
    }else{
      $attrs = $extOptionAttrs[$id];
    }

    if($selected !== false
      && ( ($selected === null && $id === '') // not selected, blank option is selected by default
        || (is_string($selected) || is_numeric($selected)) && $id == $selected
        || is_array($selected) && in_array($id, $selected))
    ){
      $attrs['selected'] = "selected";
    }
    $attrs_str = '';
    if(count($attrs)){
      foreach($attrs as $akey => $avalue){
        $attrs_str .= ' '. $akey.'="'. $avalue .'"';
      }
    }

    $value = "<option value=\"$id\"".$attrs_str.">$value</option>";
  }
  static function generateOption($value, $id, $selected = false, $extOptionAttrs = false){
    self::generateOptionsWalk($value, $id, $selected,$extOptionAttrs);
    return $value;
  }

  function getExtOptionAttrs($optionId){
    if(!array_key_exists($optionId, $this->extOptionAttrs)){
      return array();
    }
    return (array)$this->extOptionAttrs[$optionId];
  }
  function setExtOptionAttr($optionId, $extAttrName, $extAttrValue){
    if(!array_key_exists($optionId, $this->extOptionAttrs)){
      $this->extOptionAttrs[$optionId] = array();
    }
    $this->extOptionAttrs[$optionId][$extAttrName] = $extAttrValue;
    return true;
  }

  /**
   * @param array $values значения для генерации OPTION-элементов в формате array(optionValue => optionText[, ...])
   * @return boolean
   */
  function setValues($values){
    if(!is_array($values)) return false;
    if(!is_array($this->values))
      $this->values = array();

    foreach($values as $k => $v){
      if(is_array($v) && array_key_exists('values', $v) && is_array($v['values'])){
        $gInx = 'optgroup_'.
          (array_key_exists('id', $v)
            ? $v['id']
            : (count($this->values) + 1));

        if(!array_key_exists($gInx, $this->values)){
          $this->values[$gInx] = new \Verba\Html\SelectOptgroup($v);
        }elseif($this->values[$gInx] instanceof \Html\SelectOptgroup){
          $this->values[$gInx]->applyConfig($v);
        }
      }elseif(settype($v, 'string')){
        $this->values[$k] = $v;
      }
    }
  }
  function getValues(){
    return $this->values;
  }
  function clearValues(){
    $this->values = array();
  }
  function setSize($var){
    if(($var = intval($var)) && $var > 0){
      $this->size =  $var;
    }
  }
  function getSize(){
    return $this->size;
  }
  function makeSizeTagAttr(){
    if($this->getMultiple()){
      if(is_numeric($this->size)){
        $size = $this->size;
      }elseif($this->size === null && count($this->values) < 30){
        $size = count($this->values);
      }
    }
    return isset($size) ? 'size="'.$size.'"' : '';
  }

  function setMultiple($var){
    $this->multiple = (bool)$var;
  }

  /**
   * @return bool
   */
  function getMultiple(){
    return $this->multiple;
  }
  function makeMultipleTagAttr(){
    return $this->multiple
      ? 'multiple="multiple"'
      : '';
  }

  /**
   * @param $val array array('value' => '', 'text' => '')
   */
  function setBlankoption($val = '', $text = '', $extAttrs = null){
    if(is_array($val)){
      if(count($val)) {
        if (array_key_exists('value', $val)) {
          $this->blank_option = array($val['value'], $val['text']);
        } elseif (array_key_exists(0, $val)
          && array_key_exists(1, $val)
        ){
          $this->blank_option = $val;
        }
      }else{
        $this->blank_option = array('', '');
      }
    }elseif(is_string($val) && is_string($text) ){
      $this->blank_option = array($val, $text);
    }

    if(is_array($extAttrs)
      && is_array($this->blank_option)
      && array_key_exists(0, $this->blank_option)){
      $this->extOptionAttrs[$this->blank_option[0]] = $extAttrs;
    }
  }
  /**
   * @return bool|array
   */
  function getBlankoption(){
    return $this->blank_option;
  }
  function unsetBlankOption(){
    $this->blank_option = null;
  }

  function prepareEAttrs(){

    $ia = parent::prepareEAttrs();
    $ia['mlt'] = $this->makeMultipleTagAttr();
    $ia['size'] = $this->makeSizeTagAttr();

    return $ia;
  }

  function makeE(){

    $this->fire('makeE');
    if(is_string($this->E) && strlen($this->E)){
      goto goreturn;
    }
    /* $this->blank_option === null &&  */
    if(in_array('required', $this->classes)){
      $this->blank_option = false;
    }

    $this->options = $this->makeOptions();
    $tag = $this->getTag();

    $this->setE("<"
      . $tag
      . $this->prepareEAttrsImploded()
      . ">"
      . ((string)$this->options)
      . "</$tag>");

    goreturn:
    $this->fire('makeEFinalize');
    return $this->E;
  }

  function exportAsCfg(){
    $r = parent::exportAsCfg();
    if($this->getMultiple() !== null) $r['multisize'] = $this->getMultiple();
    if($this->getSize() !== null) $r['size'] = $this->getSize();
    if(is_array($this->getValues())) $r['values'] = $this->getValues();
    $r['blankoption'] = $this->getBlankoption();

    return $r;
  }


  function appendValue($value, $text){
    $this->addValue($value, $text);
  }

  function prependValue($value,$text){
    $this->addValue($value, $text, 'prepend');
  }

  function addValue($value, $text, $side = 'append'){
    $v = array($value => $text);
    if(!is_array($this->values)){
      $this->values = $v;
    }else{
      switch($side){
        case 'prepend':
          $this->values = $v + $this->values;
          break;
        case 'append':
        default:
          $this->values = $this->values + $v;
      }
    }
  }
}
