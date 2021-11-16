<?php
namespace Verba\Act\MakeList\Filter;

class Multifield extends \Verba\Act\MakeList\Filter{
  public $captionLangKey = 'list filters multifield';
  public $ftype = 'multifield';

  public $attrs = array();
  public $fattrs = array();

  function applyValue(){
    if(!count($this->attrs)){
      return;
    }
    $wgAlias = $this->makeWhereAlias();
    $this->list->QM()->removeWhere($wgAlias);
    $GW = $this->list->QM()->addWhereGroup($wgAlias);
    if($this->value){
      foreach($this->attrs as $cAttr){
        $A = $this->oh->A($cAttr);
        if(!$A){
          $this->log()->error('Unexists attribute used for filter attr');
          continue;
        }
        if($A->isLcd()){
          $cAttr = $cAttr.'_'.SYS_LOCALE;
        }
        $GW->addWhere('%'.$this->value.'%', $wgAlias.'_'.$cAttr, $cAttr, false, 'LIKE', '||');
      }
      if(!empty($this->fattrs)){
        foreach($this->fattrs as $fot => $fattrs){
          $_foh = \Verba\_oh($fot);
          list($fa) = $this->list->QM()->createAlias($_foh->vltT(),$_foh->vltDB());
          if(!is_array($fattrs)){
            settype($fattrs, 'array');
          }
          foreach($fattrs as $fattr){
            $GW->addWhere('%'.$this->value.'%', $wgAlias.'_'.$fattr, $fattr, $fa, 'LIKE', '||');
          }
        }
      }
    }
  }

  function build(){
    if(!count($this->attrs)){
      return '';
    }
    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);

    $this->E->setValue($this->value);

    $this->tpl->assign(array(
      'FILTER_ELEMENT' => $this->E->build()
    ));

    return $this->tpl->parse(false, 'content');
  }
}
?>