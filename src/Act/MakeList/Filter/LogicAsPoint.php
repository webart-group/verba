<?php
namespace Verba\Act\MakeList\Filter;

class LogicAsPoint extends \Verba\Act\MakeList\Filter{
  public $captionLangKey = false;
  public $values;

  public $felement = '\Html\Select';
  public $ftype = 'logic';

  function makeCaption(){
    $r = parent::makeCaption();
    if(!$r && $this->oh->isA($this->name)){
      $r = $this->oh->A($this->name)->getTitle();
    }
    return $r;
  }

  function applyValue(){
    $fSqlAlias = $this->makeWhereAlias();
    $this->list->QM()->removeWhere($fSqlAlias);
    if(isset($this->value) && $this->getValues() && array_key_exists($this->value, $this->values)){
      $this->list->QM()->addWhere($this->value, $fSqlAlias, $this->name);
    }
  }

  function setValue($val){
    if($val === ''){
      $val = null;
    }
    if($val !== null){
      $val = intval((bool)$val);
    }
    $this->value = $val;
  }

  function getValues(){
    if($this->values === null){
      $this->values = array('' => $this->getCaption()) + \Verba\Data\Boolean::getValues();

    }
    return $this->values;
  }

  function build(){
    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);

    $this->getValues();
    $this->E->setValues($this->values);

    if(isset($this->value) && array_key_exists($this->value, $this->values)){
      $this->E->setValue($this->value);
    }

    $this->tpl->assign(array(
      'FILTER_ELEMENT' => $this->E->build()
    ));

    return $this->tpl->parse(false, 'content');
  }
}
