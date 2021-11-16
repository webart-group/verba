<?php

namespace Verba\Act\Form\Element\Extension;

use Act\Form\Element\Extension as FormElementExtension;

class OfferCurrency extends FormElementExtension
{
    protected $U;
    protected $Store;

    protected $currencyId;

    function engage()
    {
        $this->currencyId = $this->fe->getValue();

        if(!$this->currencyId){
            $this->fe->listen('prepare', 'setCurrencyToE', $this);
            $this->U = \Verba\User();
            $this->Store = $this->U->Stores()->getStore();
        }
    }

    function setCurrencyToE()
    {
        $this->fe->setValue($this->getCurrencyId());
    }

    function getCurrencyId()
    {
        if (!$this->currencyId) {
            if ($this->Store) {
                $this->currencyId = $this->Store->currencyId;
            }

            if (!$this->currencyId) {
                $this->currencyId = \Verba\_mod('cart')->getCurrency()->getId();
            }
        }

        return $this->currencyId;
    }

}
