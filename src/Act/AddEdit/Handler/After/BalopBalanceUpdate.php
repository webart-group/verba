<?php
namespace Verba\Act\AddEdit\Handler\After;

use \Verba\Act\AddEdit\Handler\After;

class BalopBalanceUpdate extends After {

  protected $_allowedEdit = false;

  function run(){

    $Balop = $this->ah->getActualItem();

    if(!$Balop
      || $Balop->active != '1'
      || !$Balop->accountId
      || $Balop->balancenew !== null){
      $this->log()->error('Balop invalid. Account balances update canceled');
      return false;
    }

    $Acc = new \Mod\Account\Model\Account($Balop->accountId);

    $_acc = \Verba\_oh('account');
    $_balop = \Verba\_oh('balop');

    list($balance, $hbalance) = \Mod\Account::getInstance()->loadAccBalances(
      $Balop->accountId, $Acc->owner);

    //  Обновление балансов кошелька

    $q = "UPDATE ".$_acc->vltURI()." 
    
    SET 
    `balance` = '".\Verba\esc($balance)."', 
    `hbalance` = '".\Verba\esc($hbalance)."',
    `balance_updated` = '".date('Y-m-d H:i:s')."'

    WHERE
    `".$_acc->getPAC()."` = '".\Verba\esc($Balop->accountId)."' 
    
    LIMIT 1";

    $sqlr = $this->DB()->query($q);

    $loginfo = "Balop:" . $Balop->getId()
      . ", cause: ".$Balop->p('cause')
      . ", accountId: ".$Acc->getId()
      . ", currencyCode: ".$Acc->getCurrency()->p('code')
      . ", balance: ".$balance
      . ", hbalance: ".$hbalance
      . ", block: ".$Balop->block
      . ", holdTill: ".$Balop->holdTill;

    if($sqlr->getAffectedRows()){
      $this->log()->fin("Balance updated.".$loginfo);
    }else{
      $this->log()->flow("critical", "Balance Not Updated.".$loginfo);
    }

    //  Обновление полей balancenew и hbalancenew Балансовой Операции
    $q = "UPDATE ".$_balop->vltURI()." 
    
    SET 
    `balancenew` = '".\Verba\esc($balance)."', 
    `hbalancenew` = '".\Verba\esc($hbalance)."'

    WHERE
    `".$_balop->getPAC()."` = '".\Verba\esc($Balop->getId())."' 
    
    LIMIT 1";

    $this->DB()->query($q);

    // Обновляем данные в Объекте процесса AE
    $this->ah->resetActualData();


    return true;
  }

}
