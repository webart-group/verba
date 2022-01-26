<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;
use Mod\SnailMail\Exception;

/**
 * Class ProdCurrencyIdByStoreCurrency
 *
 *  !! Обязательный обработчик атрибута, должен генерироваться после storeId и до price
 *  Результат обязательно должен совпадать со значение currencyId для магазина
 *
 * @package Act\AddEdit\Handler\Around
 */
class ProdCurrencyIdByStoreCurrency extends Around
{
    function run()
    {

        $store = $this->ah->getExtendedData('store');

        if(!$store){
            $store = \Verba\_oh('store')->initItem(
                $this->action == 'new'
                    ? $this->ah->getTempValue('storeId')
                    : $this->ah->getExistsValue('storeId')
            );

            $this->ah->setExtendedData(['store' => $store]);
        }

        if(!$store){
            throw new Exception('Store error');
        }

        $this->value = $store->getNatural('currencyId');

        return $this->value;
    }
}
