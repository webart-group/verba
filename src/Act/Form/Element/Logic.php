<?php
namespace Verba\Act\Form\Element;

use \Html\Element;

class Logic extends Element
{
  public $templates = array(
    'element' => 'aef/fe/logic/logic.tpl',
    'label' => 'aef/fe/logic/logic-ui-label.tpl',
  );

  function makeE(){
    $this->fire('makeE');
    $this->fire('getValues');

    $h = new \Html\Hidden(parent::exportAsCfg());
    $h->setValue($this->value ? 1 : 0);

    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);
    $checkboxCfg = $this->exportAsCfg();
    $checkboxCfg['classes'] = [];
    $ui = new \Html\Checkbox($checkboxCfg);
    $ui->setOptions(array('' => ''));
    if($this->value){
      $ui->setValues(array(''));
    }
    $ui->setName('');
    $ui->setId($h->getId().'_ui');


    $this->tpl->assign(array(
      'LOGIC_HIDDEN' => $h->_build(),
      'LOGIC_HIDDEN_ID' => $h->getId(),
      'LOGIC_UI_ELEMENT' => $ui->_build(),
      'LOGIC_SWITCHER_ID' => $ui->getId(),
      'LOGIC_UI_CAPTION' => '',
    ));

    if(!$this->aef->gC('parseLabels')){
      $this->tpl->assign(array(
        'LOGIC_UI_LABEL_TEXT' => $this->getDisplayName(),
        'LOGIC_UI_LABEL_FOR_ID' => $ui->getId(),
      ));

      $this->tpl->parse('LOGIC_UI_CAPTION', 'label');
    }

    $this->setE($this->tpl->parse(false, 'element'));

    $this->fire('makeEFinalize');
  }

  function setValue($val){
    $this->value = (int)(bool)$val;
  }
}
