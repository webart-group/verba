<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use Act\AddEdit\Handler\Around;

class PaysysCheck extends Around
{
    function run()
    {
        try{
            if(!$this->value){
                throw new \Exception('Invalid Order Paysys ID:'. var_export($this->value, true));
            }

            $paysys = \Verba\_mod('payment')->getPaysys($this->value);
            if(!$paysys){
                throw new \Exception('Bad Order Paysys ID:'. var_export($this->value, true));
            }
            if(!$paysys->active){
                throw new \Exception('Order paysys is inactive. Id:'. var_export($this->value, true));
            }

            $currencyValue = $this->ah->getActualValue('currencyId');
            /**
             * @var $currency \Verba\Model\Currency
             */
            $currency = \Verba\_mod('currency')->getCurrency($currencyValue);
            if(!$currency || !$currency->isPaysysLinkExists($paysys->id, 'input')){
                throw new \Exception('Order paysys is unavaible, PaysysId:'. var_export($this->value, true).', curId: '.var_export($currencyValue, true));
            }

        }catch(\Exception $e){
            $this->log()->error($e->getMessage());
        }
        return $this->value;
    }
}
