<?php

namespace Verba\Act\Form\Element;

use \Verba\Html\Element;

class PriceCustomerstatus extends Element
{

  public $templates = array(
    'body' => 'aef/fe/pricecustomerstatus/e.tpl',
    'row-price' => 'aef/fe/pricecustomerstatus/row-price.tpl',
    'row-status' => 'aef/fe/pricecustomerstatus/row-status.tpl',
  );

  function makeE(){
    $this->fire('makeE');

    $aef = $this->aef();

    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);
    $mCustomer = \Verba\_mod('customer');

    $ps = $mCustomer->loadStatusesPrice($aef->getOtId(), $aef->getIID(), $this->getValue(), null, 3);
    $basePrice = $ps[0]['price'];
    unset($ps[0]);
    $nameBase = $this->getName();

    $Ecfg = parent::exportAsCfg();
    $priceE = new \Verba\Html\Text();
    $priceE->setName($nameBase);
    $priceE->setValue($basePrice);
    $priceE->addClasses('price-value base-price field-updateable');
    $this->tpl->assign(array(
      'PRICE_VALUE_E' => $priceE->build(),
    ));
    $this->tpl->parse('PRICE_E', 'row-price');
    $mCurrency = \Verba\_mod('currency');

    $this->tpl->assign(array('PRICE_STATUSES_ROWS' => '',));
    if(count($ps)){
      foreach($ps as $sid => $sdata){
        $stE = new \Verba\Html\Text();
        $stE->setName('status_price_'.$sid);
        $stE->setValue($sdata['price']);
        $stE->addClasses('price-status-value field-updateable');
        $stE->attr('data-cust', $sid);

        $stpE = new \Verba\Html\Text();
        $stpE->addClasses('perc-value perc-cust-'.$sid);

        $this->tpl->assign(array(
          'CUSTOMER_STATUS_TITLE' => $sdata['title'],
          'CUSTOMER_STATUS_PRICE' => $stE->build(),
          'CUSTOMER_STATUS_PERCENT' => $stpE->build(),
          'CUSTOMER_STATUS_AMOUNT' => '&gt; '.\Verba\reductionToCurrency($sdata['amount']),
        ));

        $this->tpl->parse('PRICE_STATUSES_ROWS', 'row-status', true);
      }
    }
    $containerId = $aef->getFormName().'_'.$aef->getOtId().'_'.$aef->getIID().'_statusprice';

    $jsCfg = array(
      'selector' => '#'.$containerId,
      'pot' => $aef->getOtId(),
      'piid' => $aef->getIID(),
      'url' => array(
        'status' => '/acp/h/customeradmin/status/updatestate',
        'form' => '/acp/h/gamecardadmin/update',
      )
    );

    $this->tpl->assign(array(
      'PRICE_VALUE_E' => $priceE->build(),
      'PRICE_FE_SELECTOR_ID' => $containerId,
      'PRICE_FE_SELECTOR_CFG' => json_encode($jsCfg),
    ));

    $this->setE($this->tpl->parse(false, 'body'));
    $this->fire('makeEFinalize');
  }
}
