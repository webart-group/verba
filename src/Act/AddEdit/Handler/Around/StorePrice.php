<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around\Price;
use Mod\SnailMail\Exception;

class StorePrice extends Price
{
    function run()
    {
        /**
         * получение цены
         */
        parent::run();

        /**
         * значение id валюты обязательно должно быть сформирвоано на этом этапе.
         * Подразумевается что он _обязательно_ корректно формиурется в хендлер-е
         * ProdCurrencyIdByStoreCurrency на этапе обработки атрибута currencyId
         */
        $currencyIdTo = $this->ah->getTempValue('currencyId');
        if(!$currencyIdTo) {
            throw new Exception('Undefined currency');
        }

        if($this->action == 'edit') {
            if(!isset($this->value)){
                $price = $this->ah->getExistsValue('price');
                $currencyIdFrom = $this->ah->getExistsValue('currencyId');
            } else {
                $price = $this->value;
                $currencyIdFrom = !$this->ah->getGettedValue('currencyId')
                    ? $this->ah->getExistsValue('currencyId')
                    : $this->ah->getGettedValue('currencyId');
            }
        } else {
            $price = !isset($this->value) ? null : $this->value;
            $currencyIdFrom = !$this->ah->getGettedValue('currencyId')
                ? $currencyIdTo
                : $this->ah->getGettedValue('currencyId');
        }

        if(!\Mod\Currency::i()->getCurrency($currencyIdFrom)){
            $currencyIdFrom = $currencyIdTo;
        }

        if($currencyIdFrom != $currencyIdTo){
            $price = \Mod\Currency::i()->crossConvert($price, $currencyIdFrom, $currencyIdTo);
        }

        $this->value = $price;
        return $this->value;
    }
}
