<?php

namespace Verba\Act\AddEdit\Handler\After;

use \Verba\Act\AddEdit\Handler\After;

class OrderCanceledCashback extends After
{

    //protected $allowed = array('edit');
    protected $_allowedNew = false;

    use OrderTrait;

    function run()
    {

        if (!$this->prepare()
            || !is_string($triggerRun = $this->ah->getExtendedData('__order_canceled_cashback__'))
            || $triggerRun != SYS_SCRIPT_KEY) {
            return null;
        }

        $orderCurId = $this->Order->getCurrency()->getId();

        /**
         * @var $sellerAcc \Verba\Mod\Account\Model\Account
         * @var $buyerAcc \Verba\Mod\Account\Model\Account
         */
        try {
            $sellerAcc = $this->Order->getStore()->getAccountByCur($orderCurId);
            if (!isset($sellerAcc)) {
                $this->log()->flow('critical', 'Seller Account not found while order close-complete operation. Order Id: ' . $this->Order->getId(), false);
                throw new \Exception('Operation incomplete. Order payment was not transfered');
            }

            // Снятие суммы с блокированного баланса Торговца #balance #balance_change
            $balopSellerEase = $sellerAcc->balanceUpdate(
                new \Verba\Mod\Balop\Cause\OrderPayedCanceledCashbackSellerEase(array(
                        'primitiveId' => $this->Order->getId(),
                        'Acc' => $sellerAcc,
                    )
                ));
            if (!$balopSellerEase || !$balopSellerEase->active) {
                $this->log()->flow('critical', 'Unable to create sellerEase Order Id: ' . $this->Order->getId());
                return false;
            }


            // Возврат суммы на основной баланс Покупателя #balance #balance_change

            $U_order = new \Verba\Mod\User\Model\User($this->ah->getActualValue('owner'));
            $buyerAcc = $U_order->Accounts()->getAccountByCur($orderCurId);

            $balopSellerGravity = $buyerAcc->balanceUpdate(
                new \Verba\Mod\Balop\Cause\OrderPayedCanceledCashbackBuyerGravityFinal($balopSellerEase)
            );

            if (!$balopSellerGravity || !$balopSellerGravity->active) {
                $this->log()->flow('critical', 'Unable to create sellerGravityFinal Order Id: ' . $this->Order->getId());
                return false;
            }

            // Обработка состояния оплаты
            $groupped = $this->Order->getItems(true);
            if (is_array($groupped) && count($groupped)) {
                $mProduct = \Verba\_mod('product');
                foreach ($groupped as $ot => $items) {
                    try {
                        $Ph = $mProduct->getProductHandler($ot);
                        $Ph->sellCanceled($items, $this);
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }


        } catch (\Exception $e) {
            $this->ah->log()->error($e->getMessage());
            return false;
        }

        return true;
    }
}
