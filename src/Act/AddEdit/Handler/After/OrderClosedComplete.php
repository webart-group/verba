<?php
namespace Verba\Act\AddEdit\Handler\After;

use Act\AddEdit\Handler\After;

class OrderClosedComplete extends After {

  //protected $allowed = array('edit');
    protected $_allowedNew = false;

  use OrderTrait;

  function run(){

    if(!$this->prepare()
    || !is_string($triggerRun = $this->ah->getExtendedData('__order_closed_complete__'))
      || $triggerRun != SYS_SCRIPT_KEY){
      return null;
    }


    /**
     * @var $sellerAcc \Mod\Account\Model\Account
     */
    try{
      $Store = $this->Order->getStore();
      $oCurId = $this->Order->getOCur()->getId();
      $sellerAcc = $Store->getAccountByCur($oCurId);
      if(!isset($sellerAcc)){
        $this->log()->flow('critical', 'Seller Account not found while order close-complete operation. Order Id: '.$this->Order->getId(), false);
        throw new \Exception('Operation incomplete. Order payment was not transfered');
      }
      // Получение Товаров заказа
      $items = $this->Order->getItems();
      if(!is_array($items) || !count($items)){
        throw  new \Verba\Exception\Building('Order without items');
      }
      // Берем первый товар
      $item = current($items);
      $itemServiceId = (int)$item['_extra']['data']['serviceCatId'];
      if(!$itemServiceId){
        throw  new \Verba\Exception\Building('Order Item unknown service Id');
      }

      $_cat = \Verba\_oh('catalog');
      // Извлечение данных о каталоге
      $catData = $_cat->getData($itemServiceId);
      if(!$catData){
        throw  new \Verba\Exception\Building('Order Item service catalog not found');
      }
      // Время холда в этом каталоге
      $timeHold = (float)$catData['timeHold'];

      $Seller = $Store->getUser();

      // Коэф в соответствии со статусом
      $sellerKTimeHold = (float)\Mod\Shop::getInstance()->getKTimeHoldByTrust($Seller->trust);

      // Время холда средств
      $holdTime = $timeHold * $sellerKTimeHold;


      // Если есть время холда, устанавливаем время холда для
      // балансовой операции
      if($holdTime > 0){

        $_balop = \Verba\_oh('balop');
        $_order = \Verba\_oh('order');
        // Ищем соотв. балансовую операцию
        $q = "SELECT `id` FROM ".$_balop->vltURI()."
 WHERE `primitiveOt` = '89' 
 && `primitiveId` = '".$this->Order->getId()."' 
 && `active` = 1 
 && `cause` = 'OrderPayedSellerGravity'
 && `accountId` = '".$sellerAcc->getId()."' 
";
        $sqlr = $this->DB()->query($q);
        if(!$sqlr || $sqlr->getNumRows() != 1
        || !($balopId = (int)$sqlr->getFirstValue())){
          $this->log()->flow('critical', 'Unable to find balop OrderPayedSellerGravity Order Id: '.$this->Order->getId());
          return false;
        }

        //обновляем время холда у балансовой операции
        $holdDateTime = date('Y-m-d H:i:s', time() + (3600 * $holdTime));
        $q = "UPDATE ".$_balop->vltURI()." SET holdTill = '".$holdDateTime."' WHERE id = '".$balopId."'";
        $sqlr = $this->DB()->query($q);
        if(!$sqlr || !$sqlr->getAffectedRows()){
          $this->log()->flow('critical', 'Unable to update balop holdTill Order Id: '.$this->Order->getId().', balopId: '.$balopId);
          return false;
        }

        //обновляем время холда у заказа
        $q = "UPDATE ".$_order->vltURI()." SET sumHoldTill = '".$holdDateTime."' WHERE id = '".$this->Order->getId()."'";
        $sqlr = $this->DB()->query($q);
        if(!$sqlr || !$sqlr->getAffectedRows()){
          $this->log()->flow('critical', 'Unable to update Order holdTill Order Id: '.$this->Order->getId().', balopId: '.$balopId);
          return false;
        }

      // Задержки выплаты нет - переводим средства торговцу в состояние доступно
      }else{
          \Mod\Order::getInstance()->finalOrderSellerGravity($this->Order, $sellerAcc);
      }


    }catch (\Exception $e){
      $this->ah->log()->error($e->getMessage());
      return false;
    }

    return true;
  }
}
