<?php

namespace Mod\Balop\Cause;

class OrderPayedBuyerEase extends Ordered
{
    /**
     * @var \Mod\Order\Model\Order
     */
    protected $_i;
    protected $otype = 'order';
    protected $_i_class = '\Mod\Order\Model\Order';
    protected $block = 0;

    protected $_itemClassSuffixRequired = '\Mod\Order\Model\Order';

    function calcSum()
    {
        return abs($this->_i->getByuerSum()) * -1;
    }

    function getDescription()
    {
        $r = \Verba\Lang::get('balop descriptions orderPayedBuyerEase', array(
            'order_code' => $this->Order->getCode(),
        ));
        return $r;
    }

    function validate()
    {
        if (!parent::validate()) {
            return $this->_valid;
        }

        $this->_valid = false;

        // Проверка того что Заказ оплачен
        if (!$this->Order->payed) {
            $this->log()->error('Order is unpayed');
            return false;
        }

// Проверяем совпадения id владельца счетов и владельцев Заказа
// ID покупателя берем как владельца Заказа

// в _i - Order, OrderPayed, active
        if ($this->_i->owner != $this->Acc->owner) {
            $this->log()->flow('balop-validation', 'Buyer id and Acc ownerId mismatch');
            return false;
        }

        $this->_valid = true;
        return $this->_valid;
    }
}