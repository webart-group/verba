<?php

namespace Verba\Act\AddEdit\Handler\Around\Gamebid\Resource;

use Act\AddEdit\Handler\Around;

class Recount extends Around
{
    function run()
    {
        if($this->action != 'new'){
            return $this->value;
        }

        $inputMethod = $this->ah->getTempValue('inputMethod') !== null
            ? $this->ah->getTempValue('inputMethod')
            : false;

        if(!$inputMethod){
            return false;
        }

        /**
         * @var $prodItem \Verba\Model\Item
         */
        $prodItem = $this->ah->getExtendedData('prodItem');

        if(!$prodItem || !is_object($prodItem)){
            $this->log()->error('Product Item not found');
            throw  new \Verba\Exception\Building('Bad incoming data');
        }
        $_prod = $prodItem->getOh();

        $factor = 1;
        $scale = $_prod->p('scale');
        if(!$scale){
            $scale = 1;
        }
        /**
         * @var $mShop Shop
         */
        $mShop = \Verba\_mod('shop');
        $curIdIn = $prodItem->getValue('currencyId');
        $priceIn = $prodItem->getValue('price');

        $curIdOut = $this->ah->getTempValue('currencyId');
        //$priceOut = $mShop->convertCur($priceIn, $curIdIn, $curIdOut);

        $rpm = $scale / $priceIn * $factor;
        $mpr = $priceIn * $factor / $scale;

        $total =
        $amount = false;

        // Amount method
        if($inputMethod == 'amount'
            && ($amount = (int)$this->ah->getGettedValue('amount')) > 0){
            $total = \Verba\reductionToCurrency($amount * $mpr);

            // Cost method
            // $this->getGettedValue('cost') - в номинальной валюте
        }elseif($inputMethod == 'cost'){
            $total = round(((float)$this->ah->getGettedValue('cost')), 2);
            $amount = $total * $rpm;
        }

        if(!$total || !$amount){
            $this->log()->error('Bad data or params missmatch');
            return false;
        }

        $this->ah->setGettedData(['amount' => $amount]);
        $this->ah->setGettedData(['topay' => $total]);

        return $total;
    }
}
