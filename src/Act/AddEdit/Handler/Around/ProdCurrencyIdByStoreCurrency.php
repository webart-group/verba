<?php

namespace Verba\Act\AddEdit\Handler\Around;

use Verba\Act\AddEdit\Handler\Around;
use Verba\Mod\SnailMail\Exception;
use Verba\Model\Store;
use function Verba\_oh;

/**
 * Class ProdCurrencyIdByStoreCurrency
 *
 *  !! Обязательный обработчик атрибута, должен генерироваться после storeId и до price
 *  Результат обязательно должен совпадать со значение currencyId для магазина
 *
 * @package Verba\Act\AddEdit\Handler\Around
 */
class ProdCurrencyIdByStoreCurrency extends Around
{
    function run()
    {
        $store = $this->ah->getExtendedData('store');
        if(!$store instanceof Store){
            throw new \Exception('Store error');
        }

        $this->value = $store->getNatural('currencyId');

        return $this->value;
    }
}
