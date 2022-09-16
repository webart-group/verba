<?php
namespace Verba\Mod;

class Account extends \Verba\Mod
{

    use \Verba\ModInstance;
    /**
     * @param $accId
     * @param $ownerId
     * @param $currencyId
     * @return array|bool
     */
    function loadAccBalances($accId, $ownerId = false)
    {

        if (is_object($accId) && $accId instanceof \Verba\Mod\Account\Model\Account) {
            $Acc = $accId;
            $accId = $Acc->getId();
            $ownerId = $Acc->owner;
        } else {
            $accId = (int)$accId;
            $ownerId = (int)$ownerId;
        }


        $r = array(0, 0);

        if (!$accId || !$ownerId) {
            return false;
        }

        $_balop = \Verba\_oh('balop');
        $q = "
SELECT 
  SUM(If(block =1, sumout, 0)) AS hbalance
  , SUM(If(block =1, 0, sumout)) as balance
  
  FROM " . $_balop->vltURI() . " b 
  
  WHERE b.accountId = " . $accId . "
  && b.owner = " . $ownerId . "
  && b.active = 1
  ";

        $sqlr = $this->DB()->query($q);

        if ($sqlr && $sqlr->getNumRows() == 1) {
            $row = $sqlr->fetchRow();
            $r[0] = \Verba\reductionToCurrency($row['balance']);
            $r[1] = \Verba\reductionToCurrency($row['hbalance']);
        }

        return $r;
    }

    function recalcAndSaveAccountBalances($accId)
    {

        $_account = \Verba\_oh('account');

        $Acc = new \Verba\Mod\Account\Model\Account($accId);
        if (!$Acc || !$Acc->getId()) {
            return 0;
        }

        list($b, $hb) = $this->loadAccBalances($Acc->getId(), $Acc->owner);

        if ($b == $Acc->getBalanceSum()
            || $hb == $Acc->getHBalanceSum()) {
            return true;
        }
        $q = "UPDATE " . $_account->vltURI() . " 
    SET 
      `balance` = '" . $b . "', `hbalance` = '" . $hb . "'
    WHERE 
    " . $_account->getPAC() . "='" . $Acc->getId() . "' LIMIT 1";

        $sqlr = $this->DB()->query($q);

        return $sqlr->getAffectedRows();
    }

}
