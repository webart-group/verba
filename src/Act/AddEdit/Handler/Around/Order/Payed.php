<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use \Verba\Act\AddEdit\Handler\Around;

class Payed extends Around
{
    function run()
    {
        $r = 0;
        $this->ah->addExtendedData(array(
            '__order_just_payed__' => false
        ));

        if($this->action == 'new'
            || $this->value === null
            || ($prevValue = (int)$this->getExistsValue('payed')) === 1
            || ($this->value = (int)((bool)$this->value)) != 1 ){
            return null;
        }

        // сейчас $this->value === 1
        // заявка на то, что заказ оплачен. Ищем какая указана причина
        $paymentCause = $this->ah->getExtendedData('__OrderPaymentCause');
        if(is_object($paymentCause)){
            if($paymentCause instanceof \Verba\Mod\Balop\Cause){

                $r = (int)($paymentCause->active && $paymentCause->sumout > 0);

            }elseif($paymentCause instanceof \Verba\Mod\Account\Model\Account){
                /**
                 * @var $this->_existsItem \Verba\Mod\Order\Model\Order
                 */
                $r = (int)($paymentCause->active
                    && $paymentCause->isSumApproved($this->ah->getExistsItem()->getByuerSum() * -1)
                    && $paymentCause->currencyId == $this->ah->getExistsValue('currencyId'));

            }elseif($paymentCause instanceof \Verba\Model\Item){
                $r = (int)($paymentCause->oh()->getCode() == 'balop' && $paymentCause->getId() && $paymentCause->active);
            }
        }else{
            return null;
        }

        if($r === 1){
            $this->ah->addExtendedData(array('__order_just_payed__' => SYS_SCRIPT_KEY));
            return $r;
        }
        $this->log()->error('Bad order payment cause');
        return false;
    }
}