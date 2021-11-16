<?php
namespace Verba\Act\MakeList\Worker;

use \Verba\Act\MakeList\Worker;

class AddItemClassesByAttrValue extends Worker{

  public $jsScriptFile = 'addItemClassesByAttrValue';

  public $values = array();
  public $field;

  function init(){
    $this->parent->listen('rowBefore', 'modifyRow', $this, 'addItemClassesByAttrValue_addCurrentValueToDataAttr');
  }

  function modifyRow(){
    if(!is_array($this->values) || is_null($this->field)
      || !array_key_exists($this->field, $this->parent->row)){
      return;
    }

    $this->parent->rowClass[] = $this->values[$this->parent->row[$this->field]];
    $this->parent->rowAttrs['data-lwk-aicab-'.$this->field] = $this->parent->row[$this->field];
  }
}
