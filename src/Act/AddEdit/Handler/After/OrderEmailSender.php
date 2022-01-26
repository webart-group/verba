<?php

namespace Verba\Act\AddEdit\Handler\After;

use \Verba\Act\AddEdit\Handler\After;

class OrderEmailSender extends After
{

    use OrderTrait;

    function run()
    {

        if (!$this->prepare()) {
            return false;
        }

        return $this->action == 'new'
            ? $this->runNew()
            : $this->runEdit();

    }

    function runNew()
    {
        $orderCreateData = $this->ah->getExtendedData('orderCreateData');

        if (is_object($orderCreateData) && isset($orderCreateData->sendEmails) && !$orderCreateData->sendEmails) {
            return null;
        }

        $silenceClient = (bool)$this->ah->getExtendedData('silenceClient');
        $silenceStaff = (bool)$this->ah->getExtendedData('silenceStaff');
        if ($silenceClient && $silenceStaff) {
            return true;
        }

        $bEmail = new \order_emailOrderCreated(null, array(
            'Order' => $this->Order,
            'ae' => $this->ah,
        ));
        $bEmail->run();

        //sending to site staff
        if (!$silenceStaff) {
            $recipients = $this->mOrder->gC('mailing to creation');
            if (!$bEmail->sendTo($recipients)) {
                $this->log()->error('Order staff-notify sending error');
            }
        }

        //sending to client
        if (!$silenceClient && $this->Order->email) {
            /**
             * @var $bEmailClient \order_emailOrderCreated
             */
            $bEmailClient = $bEmail->customizeIt('client');
            $bEmailClient->run();
            if (!$bEmailClient->sendTo($this->Order->email)) {
                $this->log()->error('Order customer sending email error');
            }
        }

        return true;
    }

    function runEdit()
    {

        $status_exists = $this->ah->getExistsValue('status');
        $status_new = $this->ah->getActualValue('status');

        if (!$status_new || $status_new == $status_exists) {
            return null;
        }

        $bEmail = new \order_emailStatusUpdated(null, array(
            'Order' => $this->Order,
            'ae' => $this->ah,
        ));
        $bEmail->run();

        $mOrder = \Verba\_mod('order');

        // send to staff
        $silenceStaff = (bool)$this->ah->getExtendedData('silenceStaff');
        $recipients = $mOrder->gC('mailing to statusUpdate');
        if (!$silenceStaff && is_array($recipients) && count($recipients)) {
            if (!$bEmail->sendTo($recipients)) {
                $this->log()->error('Order staff-notify sending error');
            }
        }

        // send to shop
        if (is_string($isJustPayed = $this->ah->getExtendedData('__order_just_payed__'))
            && $isJustPayed === SYS_SCRIPT_KEY
        ) {
            $Store = $this->Order->getStore();
            if (!is_string($shopEmail = $Store->getOperatorEmail())) {
                $this->log()->error('Store email not found');
            } else {
                $bEmailShop = $bEmail->customizeIt('shop');
                $bEmailShop->run();

                if (!$bEmailShop->sendTo($shopEmail)) {
                    $this->log()->error('Error sending Order status update email to shop');
                }
            }
        }

        // send to Client
        $silenceClient = (bool)$this->ah->getExtendedData('silenceClient');
        if (!$silenceClient && $this->Order->email) {
            $bEmailClient = $bEmail->customizeIt('client');
            $bEmailClient->run();
            if (!$bEmailClient->sendTo($this->Order->email)) {
                $this->log()->error('Order customer sending email error');
            }
        }

        return true;

    }
}
