<?php
namespace Verba\Act\MakeList\Filter;

class VariantsBase extends \Verba\Act\MakeList\Filter {

  public $name = '';
  public $attr = '';
  public $values;
  public $value = array();
  public $avaible = array();

  public $felement = '\FormElement';

  public $templates = array(
    'content' => 'list/default/filters/items/VariantsBase/variant.tpl',
    'option' => 'list/default/filters/items/VariantsBase/variant_option.tpl',
  );
  static $qm;
  static $join = array();
  static $where = array();

  function __construct($list, $cfg){
    parent::__construct($list, $cfg);
    $this->A = $this->oh->A($this->attr);
    if(!$this->name && $this->A){
      $this->name = $this->A->getCode();
    }
  }

  function extractValue(){
    $rawValue = $this->C->getFilterValue($this->getAlias());
    if(!isset($rawValue)){
      if($this->globalStoreName
        && isset($_SESSION['listGlobalFilters'][$this->globalStoreName])){
        $this->value = $_SESSION['listGlobalFilters'][$this->globalStoreName];
        return;
      }
      return;
    }
    $this->value = explode(',',$rawValue);
    $vals = array_keys($this->getValues());
    $this->value = array_intersect($this->value, $vals);
    // If GlobalStoreName is definded save value to session
    if($this->globalStoreName){
      $_SESSION['listGlobalFilters'][$this->globalStoreName] = $this->value;
    }

  }

  function requestAvaibleOptions(){

  }

  function getValues(){
    if($this->values === null){
      $this->values = array();
      if(!$this->A){
        return $this->values;
      }
      $v = $this->A->getValues();
      if(is_array($v)){
        $this->values += $v;
      }
    }
    return $this->values;
  }

  function build(){
    $this->requestAvaibleOptions();
    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);

    if(!$this->A){
      return 'Bad attr code \''.var_export($this->attr, true).'\'';
    }
//    if(!$this->A->isPredefined()){
//      return 'Attr \''.var_export($this->attr, true).'\' is not a Predefined. Wrong filter class';
//    }
    $chName = $this->makeName();
    $this->makeId();

    $this->getValues();
    $content = '';

    $checkbox_attrs = $this->E->makeAttrs();
    foreach($this->values as $id => $value){

      if($this->avaible === true
        || is_array($this->avaible) && array_key_exists($id, $this->avaible)
      ){
        $avaible = true;
        $avaible_count = $this->avaible[$id];
      }else{
        $avaible = false;
        $avaible_count = 0;
      }

      $this->tpl->assign(array(
        'VAR_AVAIBLE_SIGN' => $avaible ? '' : 'disabled',
        'VAR_COUNT' => $avaible_count,
        'VAR_DISABLED' => $avaible ? '' : 'disabled="disabled"',
        'VAR_NAME' => $chName,
        'VAR_ID' => $id,
        'VAR_CHECKED' => in_array($id, $this->value) ? ' checked' : '',
        'VAR_TEXT' => htmlspecialchars($value),
        'VAR_ATTRS' => $checkbox_attrs,
      ));
      $content .= $this->tpl->parse(false, 'option');
    }

    if(!$this->caption && !$this->captionLangKey){
      $this->caption = $this->A->display();
    }

    $this->tpl->assign(array(
      'LIST_FILTER_CAPTION' => $this->caption,
      'LIST_FILTER_CLASS' => '',
      'VARIANTS' => $content
    ));

    return $this->tpl->parse(false, 'content');
  }
}
?>