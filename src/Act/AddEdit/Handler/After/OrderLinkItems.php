<?php

namespace Verba\Act\AddEdit\Handler\After;

use \Verba\Act\AddEdit\Handler\After;

class OrderLinkItems extends After
{
    //protected $allowed = array('new');
    protected $_allowedEdit = false;

    use OrderTrait;

    function run()
    {

        if (!$this->prepare()) {
            return false;
        }

        $orderCreateData = $this->ah->getExtendedData('orderCreateData');

        if (!is_object($orderCreateData)) {
            $this->log()->error('Order items not found');
            return null;
        }
        $_order = \Verba\_oh('order');
        $ordOtId = $_order->getId();
        $orderId = $this->ah->getIID();
        /**
         * @var $item \Mod\Cart\Item
         */
        foreach ($orderCreateData->items as $hash => $item) {
            $id = $item->getId();
            $ot_id = $item->ot_id;

            $extra = json_encode($item->getExtra(), JSON_OBJECT_AS_ARRAY);

            $promotions = $item->getPromos();
            $promotionsArr = array();
            if (is_array($promotions) && !empty($promotions)) {
                foreach ($promotions as $pid => $Discount) {
                    $promotionsArr[$pid] = $Discount->packToStore();
                }
            }
            $price = $item->getPrice();
            $promotionsSerialized = is_array($promotionsArr) && !empty($promotionsArr)
                ? serialize($promotionsArr)
                : '';
            $discount = $item->getFinalDiscount();
            $price_final = $item->getFinalPrice();
            $price_chg = $price && $price_final ? $price_final / $price : 1;

            $tformOtId = $item->getTformOtId();
            $tformId = $item->getTformId();
            /**
             * @var $Store \Verba\Model\Store
             */
            $Store = $item->getStore();
            $curr = $item->getCurrency();

            $query = "INSERT
IGNORE INTO `" . SYS_DATABASE . "`.`orders_links` (
`p_ot_id`
,`p_iid`
, `ch_ot_id`
, `ch_iid`
, `title`
, `storeId`
, `quantity`
, `price`
, `currencyId`
, `rate`
, `description`
, `hash`
, `extra`
, `promotions`
, `discount`
, `price_final`
, `price_chg`
, `tformOtId`
, `tformId`
) VALUES (
'" . $ordOtId . "'
, '" . $orderId . "'
, '" . $ot_id . "'
, '" . $id . "'
, '" . $this->DB()->escape_string($item->getTitle()) . "'
, '" . $Store->getId() . "'
, '" . $item->getQuantity() . "'
, '" . $price . "'
, '" . $item->getCurrencyId() . "'
, '" . $curr->p('rate') . "'
, '" . $this->DB()->escape_string($item->getDescription()) . "'
, '" . $item->hash . "'
, '" . $this->DB()->escape_string($extra) . "'
, '" . $this->DB()->escape_string($promotionsSerialized) . "'
, '" . $this->DB()->escape_string($discount) . "'
, '" . $this->DB()->escape_string($price_final) . "'
, '" . $this->DB()->escape_string($price_chg) . "'
, '" . $this->DB()->escape_string($tformOtId) . "'
, '" . $this->DB()->escape_string($tformId) . "'
)";
            $sqlr = $this->DB()->query($query);
            // if one item cannot be added, remove all succesfully inserted previously order items
            if (!$sqlr || !$sqlr->getAffectedRows()) {
                $this->log()->error($this->DB()->getLastError());
                $this->DB()->query("DELETE FROM `" . SYS_DATABASE . "`.`orders_links` WHERE p_ot_id = '" . $ordOtId . "' && `p_iid` = '" . $orderId . "'");
                throw new \Exception('Unable to order item:' . $item['ot_id'] . '[' . $id . '];');
            }

//      $Ph = \Verba\_mod('product')->getProductHandler($ot_id);
//      if(!$Ph->isDownloadable()){
//        continue;
//      }
//
//      $_filekey = \Verba\_oh('filekey');
//
//      $qm = new QueryMaker($_filekey, false, true);
//      $qm->addConditionByLinkedOT($ot_id, $id);
//      $qm->addLimit($item->getQuantity());
//      $qm->addOrder(array('priority' => 'd', $_filekey->getPAC() => 'a'));
//      $qm->addWhere(1, 'active');
//      $qm->addWhere(120, 'state');
//      $sqlr = $qm->run();
//      if(!$sqlr){
//        $this->log()->error('Error occur while load filekey items for orderId['.$orderId.'], goods[ot:'.$ot_id.', iid:'.$id.']');
//      }
//      if($item->getQuantity() != $sqlr->getNumRows()){
//        $this->log()->error('Loaded filekey items not match with required ['.$item->getQuantity().'/'.$sqlr->getNumRows().']. orderId['.$orderId.'], goods[ot:'.$ot_id.', iid:'.$id.']');
//      }
//      //\Verba\_mod('file');
//      while($row = $sqlr->fetchRow()){
//        $iAe = $_filekey->initAddEdit('edit');
//        $iAe->setIID($row[$_filekey->getPAC()]);
//        $iAe->setGettedObjectData(array('state' => '121'));
//        $iAe->addParents($ot_id, $id);
//        $iAe->addParents($ordOtId, $orderId);
//        $iAe->addedit_object();
//        unset($iAe);
//      }
        }


    }
}
