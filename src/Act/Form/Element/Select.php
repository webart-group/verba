<?php
namespace Verba\Act\Form\Element;

use \Html\Select as HtmlSelect;

class Select extends HtmlSelect{

  function loadValues(){

    $pd = null;

    if(!is_array($this->getValues())){

      $ot_id = $this->aef()->oh()->getID();
      /**
       * @var $pdSet \ObjectType\Attribute\Predefined\Set
       */
      $pdSet = $this->A->PdSet($ot_id);
      if($pdSet){
        $pd = $pdSet->getValues();

        if(is_array($pd)){
          $this->setValues($pd);
        }
      }
    }

    if(!$this->getObligatory()){
      if(!is_array($this->getBlankOption())){
        $this->setBlankOption();
      }
    }else{
      $this->unsetBlankOption();
    }

    return $pd;
  }
}
