<?php
namespace Verba\Act\MakeList\Handler\Row;

use Act\MakeList\Handler\Row;

class ProfileOrders extends Row {

  protected $listTpl;

  function run(){

    $this->listTpl = $this->list->tpl();
    if(isset($this->list->row['first_item_extra'])
      && is_string($this->list->row['first_item_extra']))
    {
      $this->list->rowExtended['first_item_extra'] = json_decode($this->list->row['first_item_extra'], true);
    }

    $this->list->rowExtended['Order'] = new \Mod\Order\Model\Order($this->list->row);

    $this->list->rowExtended['Cur'] =  \Mod\Currency::getInstance()->getCurrency($this->list->row['currencyId']);


    return true;
  }

}
