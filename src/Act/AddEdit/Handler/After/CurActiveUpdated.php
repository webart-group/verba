<?php
namespace Verba\Act\AddEdit\Handler\After;

use Act\AddEdit\Handler\After\CurpsActiveUpdated;

class CurActiveUpdated extends CurpsActiveUpdated{

  function run(){

    if(!$this->validate()){
      return false;
    }
    //..............................................//
    //                                              //
    //  Отражаем изменение в таблицу cpk магазинов  //
    //                                              //
    //..............................................//
    /**
     * @var $mShop \Mod\Shop;
     */
    $mShop = \Mod\Shop::getInstance();

    // обновляем значение iCurrencyActive, oCurrencyActive
    $q = "
    UPDATE `".SYS_DATABASE."`.`".$mShop->cppr_table."` 
    SET
      `iCurrencyActive` = '".$this->newValue."'
    WHERE 
      `iCurId` = '".$this->entryId."'";

    $this->DB()->query($q);

    // теперь для oCurrencyActive
    $q = "
    UPDATE `".SYS_DATABASE."`.`".$mShop->cppr_table."` 
    SET
      `oCurrencyActive` = '".$this->newValue."'
    WHERE 
      `oCurId` = '".$this->entryId."'";

    $this->DB()->query($q);


    //..............................................//
    //
    //  Отражение изменения состояния (отключения)
    //  на кошельках
    //
    //..............................................//

    if($this->newValue == 0){
      $_account = \Verba\_oh('account');
      $q = "
    UPDATE ".$_account->vltURI()." 
    SET
      `mode` = 1159
    WHERE 
      `currencyId` = '".$this->entryId."'
      && `mode` = 1158";

      $this->DB()->query($q);

      \Mod\Store::getInstance()->refreshStoresCPK();
    }



    return true;
  }
}