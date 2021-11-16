<?php
namespace Verba\Act\AddEdit\Handler\After;

use \Verba\Act\AddEdit\Handler\After;

class WithdrawalAccountBalancer extends After {

  //protected $allowed = array('new');
    protected $_allowedEdit = false;

  function run(){

    $sum = (float)$this->ah->getObjectValue('sum');
    if(!$sum || $sum <= 0){
      return null;
    }

    $accId = $this->ah->getObjectValue('accountId');
    $U = \Verba\User();
    /**
     * @var \Mod\Account\Model\Account
     */
    $Acc = $U->Accounts()->getAccount($accId);

    if(!$Acc){
      $this->ah->log()->error('Bad acc');
      return null;
    }

    $balopWithdrawalEase = $Acc->balanceUpdate(new \Mod\Balop\Cause\WithdrawalEase($this->ah->getIID()));
    if(!$balopWithdrawalEase || !$balopWithdrawalEase->active){
      $this->log()->flow('critical', 'Unable to create Withdrawal, Id: '.$this->ah->getIID());
      return false;
    }

    // Прописываем код балопа запросу на вывод
    $q = "UPDATE ".$this->ah->oh()->vltURI()." 
    SET `balopCode` = '".$balopWithdrawalEase->code."' 
    WHERE `".$this->ah->oh()->getPAC()."` = '".$this->ah->getIID()."'";

    $sqlr = $this->DB()->query($q);
    $sqlr->getAffectedRows();
    // Перевод снимаемой суммы в блок
//    $balopWithdrawalBlock = $Acc->balanceUpdate(new AccountBalopCause_WithdrawalBlock($balopWithdrawalEase));
//    if(!$balopWithdrawalBlock || !$balopWithdrawalBlock->active){
//      $this->log()->flow('critical', 'Unable to block Withdrawal, Id: '.$this->ah->getIID());
//      return false;
//    }

    return true;
  }
}
