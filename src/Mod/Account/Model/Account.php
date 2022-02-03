<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 23.08.19
 * Time: 0:06
 */

namespace Mod\Account\Model;


class Account extends \Verba\Model\Item
{

    protected $otype = 'account';
    /**
     * @var \Verba\Model\Currency
     */
    protected $currency;

    protected $_confPropsMeta = array(
        'balance' => array('dataType' => 'float'),
        'hbalance' => array('dataType' => 'float'),
        'active' => array('dataType' => 'int'),
        'currencyId' => array('dataType' => 'int'),
    );

    protected $_propsForPublic = array(
        'ot_id',
        'id',
        'mode',
        'balance',
        'hbalance',
        'currencyId',
        'active',
        'currencyId__value',
        'title',
        'mode__value',
    );

    protected function reloadBalances()
    {
        list($balance, $hbalance) = \Mod\Account::getInstance()->loadAccBalances(
            $this->getId()
            , $this->owner);

        $this->applyConfigDirect(array(
            'balance' => $balance,
            'hbalance' => $hbalance,
        ));
        return array($this->balance, $this->hbalance);
    }

    /**
     * @param $cause
     * @param bool $iid
     * @return \Model\Item | bool
     */
    function balanceUpdate($cause, $iid = false)
    {
        if (!$cause instanceof \Mod\Balop\Cause && \Verba\isOt($cause) && $iid) {
            $cause = $this->primCause($cause, $iid);
        }

        if (!is_object($cause) || !$cause instanceof \Mod\Balop\Cause) {
            throw new \Exception('Bad balop params');
        }

        $this->reloadBalances();

        $cause->setAcc($this);

        $_balop = \Verba\_oh('balop');
        $ae = $_balop->initAddEdit(array(
            'action' => 'new'
        ));

        $ae->addExtendedData(array(
            'acc' => $this,
            'cause' => $cause,
        ));
        $ae->addedit_object();
        $balopItem = $ae->getActualItem();

        if ($balopItem && $balopItem->active && $balopItem->sumout != 0) {
            $this->applyConfigDirect(array(
                'balance' => $balopItem->balancenew,
                'hbalance' => $balopItem->hbalancenew,
            ));
        }

        return $balopItem;
    }

    function primCause($ot, $iid)
    {
        return $this->createCause(
            \Verba\_oh($ot)->getCode(),
            array(
                'iid' => $iid
            )
        );
    }

    function createCause($classNameSuff, $data)
    {
        $classNameBase = '\Mod\Balop\Cause';
        $className = $classNameBase .'\\' . ucfirst($classNameSuff);
        if (!class_exists($className, false)) {
            $className = $classNameBase;
        }

        return new $className($data);
    }

    /**
     * @return \Verba\Model\Currency
     */
    function getCurrency()
    {
        if ($this->currency === null) {
            $this->currency =  \Mod\Currency::getInstance()->getCurrency($this->currencyId);
        }
        return $this->currency;
    }

    function getBalanceSum($isblock = false)
    {
        return $isblock
            ? $this->getHBalanceSum()
            : $this->balance;
    }

    function getHBalanceSum()
    {
        return $this->hbalance;
    }

    function getFullBalanceSum()
    {
        return $this->getCurrency()->round($this->hbalance + $this->balance);
    }

    /**
     * @param $sum
     * @param bool $balanceType true - blocked, false - avaible
     * @param bool $isInternal
     * @return bool
     * @throws \Exception
     */
    function isSumApproved($sum, $balanceType = false, $isInternal = false)
    {
        $sum = (float)$sum;
        if ($sum == 0) {
            $this->log()->error(\Lang::get('account warns bad_sum'));
            return false;
        }
        // Пополнение - $gravity будет равно true, иначе это списание
        $gravity = $sum > 0;

        // 1160 - заблокировано
        if ($this->mode == 1160 && !$isInternal) {

            $this->log()->error(\Lang::get('account warns inactive_cause_mode blocked'));
            return false;

            // 1159 - только вывод
        } elseif ($this->mode == 1159) {

            // Пополнение в этом режиме возможно
            // только внутри счета
            if ($gravity && !$isInternal) {
                $this->log()->error(\Lang::get('account warns inactive_cause_mode only_output'));
                return false;
            }


        } else {
            if ($this->mode != 1158) {
                $this->log()->error(\Lang::get('account warns inactive_cause_mode common'));
                return false;
            }
        }

        if ($sum < 0) {
            $accBalanceSum = $this->getBalanceSum($balanceType);
            if ($accBalanceSum < abs($sum)) {
                throw new \Exception(\Lang::get('account warns if'));
            }
        }

        return true;
    }

    function exportForPublic()
    {
        $r = $this->getPropsNatural($this->_propsForPublic);
        //$r['complexModeTitle'] = $this->getComplexModeTitle();
        $r['fbalance'] = \Verba\reductionToCurrency($r['hbalance'] + $r['balance']);
        return $r;
    }
//  function getComplexModeTitle(){
//
//    switch($this->getNatural('mode')){
//      case 1158:
//        $key = !$this->active
//          ? 'only-out'
//          : 'full';
//        break;
//      case 1159:
//        $key = 'only-out';
//        break;
//      case 1160:
//        $key = 'blocked';
//        break;
//
//      default:
//        $key = 'unk';
//    }
//
//    return \Verba\Lang::get('account warns complex-mode '.$key);
//  }

}
