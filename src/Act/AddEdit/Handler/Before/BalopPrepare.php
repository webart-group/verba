<?php
namespace Verba\Act\AddEdit\Handler\Before;

use Act\AddEdit\Handler\Before;

class BalopPrepare extends Before {

  //protected $allowed = array('new');
    protected $_allowedEdit = false;

  function run(){
    try {
      $cause = $this->ah->getExtendedData('cause');
      if (!is_object($cause) || !$cause instanceof \Verba\Mod\Balop\Cause) {
        throw new \Exception('Bad balop params');
      }

      $Acc = $this->ah->getExtendedData('acc');
      if (!is_object($Acc) || !$Acc instanceof \Verba\Mod\Account\Model\Account) {
        throw new \Exception('Bad balop params');
      }

      $this->ah->resetGettedData();

      $mShop = \Mod\Shop::getInstance();
      $sum = $cause->getSum();
      $currencyId = $cause->getCurrencyId();

      list($block, $holdTill) = $cause->getBlockParams();

      list($balanceBefore, $hbalanceBefore) = \Mod\Account::getInstance()->loadAccBalances(
        $Acc->getId(), $Acc->owner);

      $data = array(
        'accountId' => $Acc->getId(),
        'currencyId' => $currencyId,
        'sum' => $sum,
        'cause' => $cause->toSignature(),
        'causeOt' => $cause->getCauseOt(),
        'causeId' => $cause->getCauseId(),
        'primitiveOt' => $cause->getPrimitiveOt(),
        'primitiveId' => $cause->getPrimitiveId(),
        'accCurrencyId' => $Acc->currencyId,
        'sumout' => $mShop->convertCur($sum, $currencyId, $Acc->currencyId),
        'crossrate' => $mShop->crossrate($currencyId, $Acc->currencyId),
        'block' => $block,
        'holdTill' => $holdTill,
        'visibility' => $cause->getBalopVisibility(),
        'description' => $cause->getDescription(),
        'balanceBefore' => $balanceBefore,
        'hbalanceBefore' => $hbalanceBefore,
      );

      $this->ah->setGettedData($data);

    }catch (\Exception $e){
      $this->ah->log()->error($e->getMessage());
      return false;
    }

    return true;
  }

}
