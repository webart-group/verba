<?php

namespace Verba\Act\Form\Element\Extension;

use Act\Form\Element\Extension;

class WithdrawalPrequisite extends Extension
{
  /**
   * @var \Verba\Act\Form\Element\ForeignSelect
   */
  public $fe;
  public $ps_pc;
  /**
   * @var $Cur \Verba\Model\Currency
   */
  public $Cur;
  /**
   * @var $Acc \Mod\Account\Model\Account
   */
  public $Acc;
  public $phrases;

  function engage(){

    $this->fe->valueFormater = array($this, 'formateValue');

    /**
     * @var $aef \Verba\Act\Form
     */
    $aef = $this->fe->ah();
    $this->Acc = $aef->getExtendedData('Acc');
    $this->Cur =  \Mod\Currency::getInstance()->getCurrency($this->Acc->currencyId);
    $this->phrases = \Verba\Lang::get('withdrawal form preqtitles');
  }

  /**
   * @param $OItem \Verba\Model\Item prequisite
   * @var $aef \Verba\Act\Form\Element\ForeignSelect
   * @return string
   */
  function formateValue($OItem){

    $r = array();
    $psId = $OItem->getNatural('paysysId');

    foreach($this->fe->fieldsToTitle as $fcode => $nomatter){
      $r[] = $OItem->getValue($fcode);
    }

    $tax = \Verba\reductionToCurrency($this->Cur->getPaysysLinkValue('output', $psId, 'kOut'));

    if($tax < 0){
      $lkey = 'bonus';
      $taxstr = $tax * -1;
    }else{
      $lkey = 'tax';
      $taxstr = $tax ;
    }

    $r[] = str_replace("{tax}", $taxstr, $this->phrases[$lkey]);

    // добавление data-атрибута в опшен со знаение комиссии на вывод
    // для последуюго использования на клиенте
    $this->fe->setExtOptionAttr($OItem->getId(), 'data-tax', $tax);

    return implode(', ', $r);
  }
}
