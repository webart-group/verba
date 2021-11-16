<?php
namespace Verba\Act\MakeList\Handler\Row;

class ProfilePurchase extends ProfileOrders {

  protected $listTpl;

  function run(){

    parent::run();

    switch($this->list->row['status']){
      case '20':
        $status_class_suffix = 'awaiting-payment';
        break;
      case '21':
        $status_class_suffix = 'payed';
        break;
      case '22':
        $status_class_suffix = 'canceled-cashback';
        break;
      case '23':
        $status_class_suffix = 'canceled-closed';
        break;
      case '24':
        $status_class_suffix = 'error';
        break;
      case '25':
        $status_class_suffix = 'successfully-completed';
        break;
      default:
        $status_class_suffix = 'unknown';
    }
    $this->list->rowClass[] = 'order-status-'.$status_class_suffix;

    return true;
  }
}
