<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use Act\AddEdit\Handler\Around;
use Mod\Order;
use Mod\Customer;

class Status extends Around
{
    function run()
    {
        if($this->action != 'edit' || $this->value === null){
            return $this->value;
        }

        $existsValue = $this->ah->getExistsValue('status');
        if($this->value == $existsValue){
            return $this->value;
        }

        $mOrder = Order::getInstance();
        $order = $mOrder->getOrder($this->ah->getIID());
        $mCustomer = Customer::i();
        $customerSumUpdate = ($customerSumUpdate = $this->ah->getExtendedData('customerUpdateTotalSum')) !== null
            ? (bool)$customerSumUpdate
            : true;

        $payed = $this->ah->getActualValue('payed');

        /**
         * ОПЛАЧЕН
         */
        if($this->value == 21){

            // Проверяем что присутствует подпись факта оплаты
            $justPayedSign = $this->ah->getExtendedData('__order_just_payed__');

            if(!$payed || !$justPayedSign || $justPayedSign != SYS_SCRIPT_KEY){
                $this->log()->error('order ae_errors statusFalsePayed');
                return false;
            }

            /*
                  //update downloadable content status;
                  $files = $order->getDownloadableItems();
                  do{
                    if(!$files || !count($files)){
                      break;
                    }
                    $state = '122';
                    foreach($files as $cfile){
                      $oh = \Verba\_oh($cfile['ot_id']);
                      $ae = $oh->initAddEdit(array('action' => 'edit'));
                      $ae->setIID($cfile[$oh->getPAC()]);
                      $ae->setGettedObjectData(array('state' => $state));
                      $ae->addedit_object();
                    }
                  }while(false);
            */
            if($customerSumUpdate){
                $mCustomer->updateCustomerStatusByOrderTotal($order);
            }

            // * ОТМЕНЕН, возврат
        }elseif($this->value == 22){

            if(!is_string($action_script_sign = $this->ah->getExtendedData('__seller_cancel_cashback_script_key'))
                || $action_script_sign !== SYS_SCRIPT_KEY
                || $payed != 1
                || $existsValue != 21)
            {
                $this->log()->error('order ae_errors mustBePayed');
                return false;
            }

            $this->ah->addExtendedData(array('__order_canceled_cashback__' => SYS_SCRIPT_KEY));

            // * ОТМЕНЕН, ЗАКРЫТ
        }elseif($this->value == 23){
            /*
                  $files = $order->getDownloadableItems();
                  if($files && count($files)){
                    $state = '120';
                    foreach($files as $cfile){
                      $oh = \Verba\_oh($cfile['ot_id']);
                      if(!isset($toUnlink[$cfile['ot_id']])){
                        $toUnlink[$cfile['ot_id']] = array();
                      }
                      $toUnlink[$cfile['ot_id']] = $cfile[$oh->getPAC()];
                      $ae = $oh->initAddEdit(array('action' => 'edit'));
                      $ae->setIID($cfile[$oh->getPAC()]);
                      $ae->setGettedObjectData(array('state' => $state));
                      $ae->addParents($cfile['__pot'], $cfile['__piid']);
                      $ae->addedit_object();
                    }
                  }
            */
            if($customerSumUpdate){
                $mCustomer->updateCustomerStatusByOrderTotal($order, false);
            }


            // * ОШИБКА ОПЛАТЫ
        }elseif($this->value == 24){

            // * Выполнен, закрыт
        }elseif($this->value == 25){
            if(!$payed){
                $this->log()->error('order ae_errors mustBePayed');
                return false;
            }
            if(!$this->ah->getActualValue('confirmedBuyer')){
                $this->log()->error('order ae_errors mustBeConfirmedBuyer');
                return false;
            }
            $this->ah->addExtendedData(array('__order_closed_complete__' => SYS_SCRIPT_KEY));

            // Иначе - ошибка
        }else{
            return false;
        }

        $groupped = $order->getItems(true);
        if(is_array($groupped) && count($groupped)){
            /**
             * @var $mProduct \Mod\Product
             */
            $mProduct = \Verba\_mod('product');
            foreach($groupped as $ot => $items){
                try{
                    /**
                     * @var $Ph \ProductType_product
                     */
                    $Ph = $mProduct->getProductHandler($ot);

                    $Ph->handleOrderStatusChange($items, $order, $this);
                }catch(\Exception $e){
                    continue;
                }
            }
        }

        return $this->value;
    }
}
