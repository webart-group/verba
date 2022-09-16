<?php
class store_priceFormater extends ListHandlerField {

  function run(){
    /**
     * @var $mCart Cart
     */
    $mCart = \Verba\_mod('Cart');
    /**
     * @var $mCurrency Currency
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
?>