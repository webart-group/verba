<?php

namespace Verba\Act\Form\Element\Extension;

use \Verba\Act\Form\Element\Extension;

class Anno extends Extension
{
  public $annotation = false;

  function engage(){

    if(!is_string($this->annotation)){
      if(!is_object($this->fe->A())){
        return false;
      }

      $this->annotation = $this->fe->A()->getAnnotation();
    }


    if(!is_string($this->annotation) || empty($this->annotation)){
      return false;
    }

    $this->fe->listen('makeEFinalize', 'addAnnotation', $this);
    return true;
  }

  function addAnnotation(){
    $formId = $this->fe->ah()->getWrapId();
    $this->fe->ah()->addJsAfter(
      '$(\'#'.$formId.' [data-toggle="tab"]\').popover({container: \'body\'});',
      'init_popover_functionality'
    );

    $annoE = '<span 
      tabindex="0"
      class="ff-anno-trigger" 
      role="button"
      data-trigger="focus"
      data-toggle="tab" 
      title="'.$this->fe->getDisplayName().'" 
      data-content="'.htmlspecialchars($this->annotation).'"></span>';

    $this->fe->E = $this->fe->E.$annoE;

  }
}
