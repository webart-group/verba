<?php
namespace Verba\Act\MakeList\Filter;

class Usergroup extends \Verba\Act\MakeList\Filter{

  public $captionLangKey = 'useradmin manage_list filters group title';
  public $ftype = 'usergroup';
  public $gvalues;

  function getGValues(){
    if($this->gvalues === null){
      $_group = \Verba\_oh('group');
      $qmg = new \Verba\QueryMaker($_group, false, true);
      $qmg->addOrder(array($_group->getPAC() => 'd'));
      $sqlr = $qmg->run();
      $this->gvalues = array('' => \Verba\Lang::get('useradmin manage_list filters group novalue'));
      if(is_object($sqlr) && $sqlr->getNumRows()){
        while($grow = $sqlr->fetchRow()){
          $this->gvalues[$grow[$_group->getPAC()]] = $grow['description_'.SYS_LOCALE];
        }
      }
    }

    return $this->gvalues;

  }
  function setValue($val){
    if($val !== null){
      $this->value = intval($val);
    }else{
      $this->value = null;
    }
  }
  function applyValue(){

    $this->list->QM()->removeWhere('usergroup');
    if(!isset($this->value)){
      return;
    }
    $this->getGValues();
    if(!array_key_exists($this->value, $this->gvalues)){
      return;
    }
    $_group = \Verba\_oh('group');
    list($pa, $pt) = $this->list->QM()->createAlias();
    list($gla, $glt) = $this->list->QM()->createAlias($_group->vltT($this->oh->getID()));
    $this->list->QM()->addCJoin(array(array('a' => $gla)),
      array(
        array('p' => array('a'=> $gla, 'f' => 'ch_iid'),
          's' => array('a'=> $pa, 'f' => $this->oh->getPAC())),
      ), false, 'usergroup');
    $this->list->QM()->addWhere("(`".$gla."`.p_ot_id = ".$_group->getID()." && `".$gla."`.p_iid = '".$this->value."')", false, 'usergroup');
  }

  function build(){
    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);
    $E = new \Html\Select($this->ecfg);
    $this->getGValues();
    $E->setValues($this->gvalues);

    if(isset($this->value) && array_key_exists($this->value, $this->gvalues)){
      $E->setValue($this->value);
    }

    $this->tpl->assign(array(
      'FILTER_ELEMENT' => $E->build()
    ));

    return $this->tpl->parse(false, 'content');
  }
}
?>