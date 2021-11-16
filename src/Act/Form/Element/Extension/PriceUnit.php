<?php

namespace Verba\Act\Form\Element\Extension;

use Act\Form\Element\Extension as FormElementExtension;

class PriceUnit extends FormElementExtension
{

    public $templates = array(
        'content' => '/aef/exts/priceUnit/content.tpl'
    );

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

        if(!$this->currencyId){
            $this->currency = \Verba\_mod('cart')->getCurrency();
            $this->currencyId = $this->currency->getId();
        }else{
            $this->currency = \Verba\_mod('currency')->getCurrency($this->currencyId);
        }
        return $this->currency;
    }

    function wrapElement()
    {
        $this->fe->E;

        $this->getCurrency();
        $this->tpl->define($this->templates);
        $this->tpl->assign(array(
            'ELEMENT' => $this->fe->E,
            'CURRENCY_SHORT' => $this->currency->p('symbol'),
        ));

        $this->fe->E = $this->tpl->parse(false, 'content');
    }

    function setCurrencyId($val)
    {
        $this->currencyId = (int)$val;
    }
}
