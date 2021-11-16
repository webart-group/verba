<?php
namespace Verba\Act\Form\Element\Extension;

use Act\Form\Element\Extension;

/**
 *  Заполняет селект выбранными значениями стороннего атрибута.
 *  Значения стороннего атрибута доступны через Расширенные данные - prodItem
 *
 */
class ValuesByForeignSelected extends Extension{

  public $src_attr;
  public $skipIfEmpty = 1;

  function engage(){

    $values = $this->loadValues();

    if(!is_array($values) || !count($values)){
      if($this->skipIfEmpty){
        $this->fe()->setHidden(true);
        return true;
      }else{
        $values = [];
      }
    }

    $this->fe()->setValues($values);
    return true;
  }

  function loadValues(){
    $values = array();
    /**
     * @var $Item \Verba\Model\Item
     * @var $A \ObjectType\Attribute
     */
    $Item = $this->ah()->getExtendedData('prodItem');
    if(!$Item){
      return false;
    }

    $A = $Item->oh()->A($this->src_attr);
    $acode = $A->getCode();
    if($A->getType() == 'multiple'){

      $values = $Item->getPreparedIds($this->src_attr);

    }elseif($A->isPredefined()){
      $currentId = $Item->getNatural($acode);
      if(!$currentId){
        return false;
      }
      $values = $A->PdSet()->getValues();
      $values = array(
        $currentId => $values[$currentId]
      );

    }
    return $values;
  }
}
