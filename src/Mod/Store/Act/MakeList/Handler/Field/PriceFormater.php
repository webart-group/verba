<?php
namespace Verba\Mod\Store\Act\MakeList\Handler\Field;

use Verba\Act\MakeList\Handler\Field;

class PriceFormater extends Field {

    function run(){
        /**
         * @var $mCart \Verba\Mod\Cart
         */
        $mCart = \Verba\_mod('Cart');
        /**
         * @var $mCurrency \Verba\Mod\Currency
         */
        $mCurrency = \Verba\_mod('Currency');
        /**
         * @var $userCurrency \Verba\Model\Currency
         */
        $userCurrency = $mCart->getCurrency();
        $price = $mCurrency->crossConvert(
            $this->list->row['price'],
            $this->list->row['currencyId'],
            $userCurrency->getId()
        );
        //$this->list->row['minPc']
        $r = \Verba\reductionToCurrency($price * $this->list->row['minPc']);
        return $r;
    }

}
