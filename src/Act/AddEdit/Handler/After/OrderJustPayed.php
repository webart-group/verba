<?php

namespace Verba\Act\AddEdit\Handler\After;

use Act\AddEdit\Handler\After;
use Mod\Notifier\Event;
use Mod\Notifier\Pipe;
use Verba\User\Model\User;

class OrderJustPayed extends After
{
    //protected $allowed = array('edit');
    protected $_allowedNew = false;

    use OrderTrait;

    function run()
    {

        if (!$this->prepare()) {
            return false;
        }

        $status_exists = $this->ah->getExistsValue('status');
        $status_new = $this->ah->getActualValue('status');
        if (!$status_new || $status_new == $status_exists || $status_new != 21) {
            return null;
        }

        if (!is_string($isJustPayed = $this->ah->getExtendedData('__order_just_payed__'))
            || $isJustPayed != SYS_SCRIPT_KEY
        ) {
            $this->log()->error('Unable to verify order payment: `justPayed`-sign is wrong');
            return false;
        }

        $U_owner = new U($this->ah->getActualValue('owner'));
        $orderCurId = $this->Order->getCurrency()->getId();
        $oCurId = $this->Order->getOCur()->getId();

        $buyerAcc = $U_owner->Accounts()->getAccountByCur($orderCurId);

        /**
         * @var $cAcc \Mod\Account\Model\Account
         * @var $buyerAcc \Mod\Account\Model\Account
         * @var $sellerAcc \Mod\Account\Model\Account
         */
        if (!isset($buyerAcc)) {
            $this->log()->flow('critical', 'Buyer Account not found. Order Success Payed Balop process interrupted. Order Id: ' . $this->Order->getId());
            return false;
        }

        // Создание балансовой операции #balance_change
        // Снятие средств с баланса Покупателя
        // для последующего зачисления на баланс Торговца #balance #balance_change
        $easeBuyerSellerBalop = $buyerAcc->balanceUpdate(new \Mod\Balop\Cause\OrderPayedBuyerEase(array('_i' => $this->Order)));
        if (!$easeBuyerSellerBalop || !$easeBuyerSellerBalop->active) {
            $this->log()->error('Unable to create easeBuyerSellerBalop Order Id: ' . $this->Order->getId());
            return false;
        }

        $sellerAcc = $this->Order->getStore()->getAccountByCur($oCurId);
        if (!isset($sellerAcc)) {
            $this->log()->flow('critical', 'Seller Account not found. Order Success Payed Balop process interrupted. Order Id: ' . $this->Order->getId());
            return false;
        }
        // Зачисление средств на баланс Торговца #balance #balance_change
        $gravitySellerBalop = $sellerAcc->balanceUpdate(new \Mod\Balop\Cause\OrderPayedSellerGravity($easeBuyerSellerBalop));
        if (!$gravitySellerBalop || !$gravitySellerBalop->active) {
            $this->log()->flow('critical', 'Unable to create gravitySellerBalop Order Id: ' . $this->Order->getId());
            return false;
        }

        // Обработка состояния оплаты
        $groupped = $this->Order->getItems(true);
        if (is_array($groupped) && count($groupped)) {
            $mProduct = \Verba\_mod('product');
            foreach ($groupped as $ot => $items) {
                try {
                    $Ph = $mProduct->getProductHandler($ot);
                    $Ph->sold($items, $this);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        //Отправка оповещения в канал
        /**
         * @var $mNotifier \Mod\Notifier
         */
        $mNotifier = \Verba\_mod('notifier');
        $mStore = \Mod\Store::getInstance();

        $event = new Event(['id' => $this->Order->getId()], 'newOrder');

        $mNotifier->pipe(Pipe::ALIAS_STORE, $mStore->getStoreChannelName($this->Order->getStore()))
            ->send($event);

        return true;
    }
}
