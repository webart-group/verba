<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use \Verba\Act\AddEdit\Handler\Around;

class CurrencyCheck extends Around
{
    function run()
    {
        try{

            if(!$this->value){
                throw new \Exception('Invalid Order currency ID:'. var_export($this->value, true));
            }
            /**
             *
             */
            $Cart = $this->ah->getExtendedData('cart');
            if($Cart && $Cart->getCurrencyId() != $this->value){
                $Cart->currencyChange($this->value);
            }
            /**
             * @var $currency \Verba\Model\Currency
             */
            $currency = \Verba\_mod('currency')->getCurrency($this->value);
            if(!$currency){
                throw new \Exception('Bad Order currency ID:'. var_export($this->value, true));
            }
            if(!$currency->p('active')){
                throw new \Exception('Order currency inactive. Id:'. var_export($this->value, true));
            }

        }catch(\Exception $e){
            $this->log()->error($e->getMessage());
        }
        return $this->value;
    }

}
