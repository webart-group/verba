<?php

namespace Verba\Act\Form\Element\Extension;

use Act\Form\Element\Extension as FormElementExtension;

class OfferBidPrice extends FormElementExtension
{
    public $templates = array(
        'content' => '/aef/exts/offerbidPrice/content.tpl'
    );

    protected $U;
    protected $Store;

    protected $currencyId;
    protected $currency;

    function engage()
    {
        $this->fe->listen('makeEFinalize', 'wrapElement', $this);
    }

    function getCurrency()
    {
        if ($this->currency !== null) {
            return $this->currency;
        }
        $this->currency = false;

        $curFe = $this->ah->getAefByAttr('currencyId');
        if(!$curFe){
            throw  new \Verba\Exception\Building('Currency attribute must presents');
        }
        $this->currencyId = $curFe->getExtension('OfferCurrency')->getCurrencyId();
        $this->currency = \Verba\_mod('currency')->getCurrency($this->currencyId);

        return $this->currency;
    }

    function setCurrencyId($val)
    {
        $this->currencyId = (int)$val;
    }

    function wrapElement()
    {
        $this->fe->E;

        $currency = $this->getCurrency();
        $curFe = $this->ah()->getAefByAttr('currencyId');
        $this->tpl->define($this->templates);
        $this->tpl->assign(array(
            'ELEMENT' => $this->fe->E,
            'CURRENCY_SHORT' => $currency->p('symbol'),
            'CURRENCY_ID_E_ID' => $curFe->getId(),
        ));

        $this->fe->E = $this->tpl->parse(false, 'content');
    }
}
