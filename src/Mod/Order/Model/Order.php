<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 16:19
 */
namespace Verba\Mod\Order\Model;

class Order extends  \Model\Item
{
    protected $otype = 'order';
    public $id;
    protected $items;
    protected $transactions;
    protected $paysys;
    /**
     * @var $currency \Verba\Model\Currency
     */
    protected $currency;
    protected $customer;
    protected $validLocale;
    /**
     * @var \Model\Store
     */
    protected $Store;

    /**
     * @var \Verba\Mod\User\Model\User
     */
    private $_Buyer;


    protected $__statusUrl;
    /**
     * @var string Примечание к платежу, передаваемое на сторону платежной системы.
     * Генерируется при вызове гет-метода
     */
    protected $__billTitle;
    // сумма, зачисляемая на баланс Оплачивающего заказ
    protected $__sumToBalance;

    // сумма, зачисляемая на баланс Магазина
    protected $__sumToSeller;

    // Валюта вывода
    /**
     * @var \Verba\Model\Currency
     */
    protected $__oCur;

    protected $__url = array();

    function init(){
        $this->id = (int)$this->getNatural('id');
        $this->currency = \Verba\_mod('currency')->getCurrency($this->currencyId);
        $this->paysys = \Verba\_mod('payment')->getPaysys($this->paysysId);


        if(!\Lang::isLCValid($this->locale)){
            $this->validLocale = \Verba\Lang::getDefaultLC();
        }else{
            $this->validLocale = $this->locale;
        }
    }

//  protected function loadData($iid){
//    /**
//     * @var $mOrder Order
//     */
//    $mOrder = \Verba\_mod('order');
//    $_order = \Verba\_oh('order');
//    $idField = $mOrder->isOrderCode($iid) ? $_order->getStringPAC() : $_order->getPAC();
//
//    $qm = new \Verba\QueryMaker($_order, false, true);
//    $qm->addWhere($iid, $idField);
//    $qm->addLimit(1);
//
//    $sqlr = $qm->run();
//    if(!$sqlr || !$sqlr->getNumRows()){
//      return false;
//    }
//
//    return  $sqlr->fetchRow();
//  }

    function getId(){
        return $this->id;
    }

    function getCode(){
        return $this->getNatural('code');
    }

    function getIid(){
        return $this->data['id'];
    }

    function getShipping(){
        return $this->getValue('shipping');
    }

    function getTotal(){
        return $this->getValue('total');
    }

    function getTopay(){
        return $this->getValue('topay');
    }

    function getTopayUnit(){
        return $this->currency->short;
    }

    function getItemsTotal(){
        $items = $this->getItems();
        if(!$items || !is_array($items) || !count($items)){
            return false;
        }

        $orderCurrency = $this->getCurrency();
        /**
         * @var $mShop \Verba\Mod\Shop
         */
        $mShop = \Verba\_mod('shop');

        $cost = 0;

        foreach($items as $item){
            $itemConvertedCost = $mShop->convertCur($item['price'],$item['currencyId'], $orderCurrency->getId());
            $cost += $itemConvertedCost;
        }
        return $orderCurrency->round($cost);
    }

    function getPaysysTitle(){
        return $this->paysys->title;
    }

    function getItems($grouppedByOt = false){
        if($this->items === null){
            $this->loadOrderItems();
        }
        if(!is_array($this->items) || !count($this->items) || !$grouppedByOt){
            return $this->items;
        }

        $groupped = array();
        foreach($this->items as $hash => $item){
            if(!array_key_exists($item['ot_id'], $groupped)){
                $groupped[$item['ot_id']] = array();
            }

            $groupped[$item['ot_id']][$hash] = $item;
        }
        return $groupped;
    }

    function loadOrderItems(){
        if(!$this->id){
            return false;
        }
        $_product = \Verba\_oh('product');
        $_order = \Verba\_oh('order');
        $ltable = $_order->vltT($_product->getID());
        $q = "SELECT *, `ch_ot_id` as `ot_id`, `ch_iid` as `id` 
FROM `".$_order->vltDB()."`.`$ltable`
WHERE `p_ot_id` = '".$_order->getID()."' && `p_iid` = '".$this->getIid()."'";
        $oRes = $this->DB()->query($q);
        if($oRes->getNumRows() == 0){
            return false;
        }
        $i = 0;
        // массив куда будут записаны данные для подгрузки tform данных
        $tformRqData = array();
        while($row = $oRes->fetchRow()){
            $i++;
            if(!empty($row['extra'])){
                $row['_extra'] = (array)json_decode($row['extra'], JSON_OBJECT_AS_ARRAY);
            }else{
                $row['_extra'] = array();
            }

            if(!empty($row['promotions']) && is_string($row['promotions'])){
                $row['promotions'] = unserialize($row['promotions']);
            }else{
                $row['promotions'] = array();
            }
            unset($row['extra']);

            if(!empty($row['tformOtId']) && !empty($row['tformId'])){
                if(!array_key_exists($row['tformOtId'], $tformRqData)){
                    $tformRqData[$row['tformOtId']] = array();
                }
                $tformRqData[$row['tformOtId']][$row['tformId']] = $row['hash'];
            }
            $this->items[$row['hash']] = $row;
        }

        if(!empty($tformRqData)){
            foreach($tformRqData as $tformOt => $iids_hashes){
                $_ctform = \Verba\_oh($tformOt);
                if(!$_ctform instanceof \Model\Tform){
                    continue;
                }
                $tform_attrs = $_ctform->getAttrsByRole('tform-field');
                $tformItemsData = $_ctform->getData(array_keys($iids_hashes), true, $tform_attrs);
                if(is_array($tformItemsData) && count($tformItemsData)){
                    $tform_attrs_flipped = array_flip($tform_attrs);
                    foreach($tformItemsData as $tid => $tdata){
                        $this->items[$iids_hashes[$tid]]['tformData'] = array_intersect_key($tdata, $tform_attrs_flipped);
                    }
                }
            }
        }

        return $i;
    }

    function loadTransactions(){
        if(!$this->id){
            $this->transactions = array();
        }
        $mod = \Verba\_mod('payment')->getPaysysMod($this->paysysId);
        if(!$mod){
            $this->transactions = array();
            return false;
        }
        $this->transactions = $mod->loadTrans($this->id);
    }

    function getTran($id = null){
        if($this->transactions === null){
            $this->loadTransactions();
        }
        if($id === null){
            return $this->transactions;
        }

        if(!array_key_exists($id, $this->transactions)){
            return null;
        }
        return $this->transactions[$id];
    }

    function getTrans($id = null){
        return $this->getTran($id);
    }

    function getFullName(){
        return \Verba\Mod\User::getFullName(array(
            'name' => $this->name,
            'patronymic' => $this->patronymic,
            'surname' => $this->surname
        ));
    }

    function getDiscountValue(){
        return $this->discount * $this->currency->rate;
    }

    function getDiscountValuePrepared(){
        return \Verba\reductionToCurrency($this->getDiscountValue());
    }

    function getDiscountPercent(){
        $percent = 0;

        $dd = $this->getDiscountDetails();

        if(is_array($dd) && !empty($dd)){
            foreach($dd as $did => $ddetails){
                if($ddetails['affect'] == 'goods'){
                    continue;
                }
                $percent += $ddetails['percent'];
            }
        }
        return $percent;
    }

    function getDiscountPercentPrepared(){
        return \Verba\reductionToCurrency($this->getDiscountPercent());
    }

    function getDownloadableItems($groupByOt = false){
        $groupByOt = (bool)$groupByOt;
        $items = $this->getItems();
        $r = array();
        if(!is_array($items) || !$items){
            return $r;
        }
        foreach($items as $hash => $item){
            $oh = \Verba\_oh($item['ot_id']);
            $itemId = $item['ch_iid'];
            $pItem = $oh->getData($itemId, 1);
            if(!$pItem || !isset($pItem['active']) || $pItem['active'] != '1'){
                $this->log()->warning('Try to get inactive Good . Skip. $order->id:'.var_export($this->id, true).'; $pot:'.var_export($oh->getId(), true).'; $piid:'.var_export($itemId,true).';');
                continue;
            }

            $Ph = \Verba\_mod('product')->getProductHandler($oh);
            if(!$Ph->isDownloadable()){
                continue;
            }

            $downItems = $Ph->getDownItems($this->id, $oh, $itemId);
            if(!$downItems){
                $this->log()->warning('Not found Items to return for Good. Skip. $order->id:'.var_export($this->id, true).'; $pot:'.var_export($oh->getId(), true).'; $piid:'.var_export($itemId,true).';');
                continue;
            }
            if($groupByOt){
                if(!isset($groupByOt[$oh->getId()])){
                    $r[$oh->getId()] = array();
                }
                $saveTo = &$r[$oh->getId()];
            }else{
                $saveTo = &$r;
            }
            foreach($downItems as $cid => $citem){
                if(isset($citem['active']) && $citem['active'] != 1){
                    $this->log()->warning('Filekey is inactive - skip. $order->id:'.var_export($this->id, true).'; $pot:'.var_export($oh->getId(), true).'; $piid:'.var_export($itemId,true).';');
                    continue;
                }
                $citem['__pot'] = $item['ot_id'];
                $citem['__piid'] = $itemId;
                $saveTo[] = $citem;
            }
            unset($saveTo);
        }
        return $r;
    }

    function getPaysys(){
        return $this->paysys;
    }

    function getPaysysMod(){
        return \Verba\_mod('payment')->getPaysysMod($this->paysys->code);
    }

    /**
     * @return \Verba\Model\Currency
     */
    function getCurrency(){
        return $this->currency;
    }
    function getCurrencyId(){
        return $this->getNatural('currencyId');
    }

    function getCustomer(){

        if($this->customer === null){
            $this->customer = \Verba\_mod('customer')->loadProfile($this->customerId);
            if($this->customer === null){
                $this->customer = false;
            }
        }

        return $this->customer;
    }

    function getValidLocale(){
        return $this->validLocale;
    }

    function getRate(){
        return (float)$this->rate;
    }

    function getStatusUrlObj(){

        if($this->__statusUrl === null){

            $path = \Verba\_mod('order')->gC('url status').'/'.$this->getCode();

            $params = array('lc'=> $this->getValidLocale());

            $this->__statusUrl = new \Url($path);

            $this->__statusUrl->setParams($params);
        }

        return $this->__statusUrl;
    }

    function getStatusUrl(){

        $url = $this->getStatusUrlObj();

        if($url instanceof \Url){

            $r = $url->get(true);

        }
        return isset($r) ? $r : false;
    }

    function getUrlPurchase($action = ''){

        if(!array_key_exists($action, $this->__url)){
            /**
             * @var $mProfile \Verba\Mod\Profile
             */
            $mProfile = \Verba\_mod('profile');
            $this->__url[$action] = $mProfile->getPurchaseActionUrl($this, $action);
        }

        return $this->__url[$action];
    }

    function getStore(){
        if($this->Store === null){
            $this->Store = $this->loadStore();
        }
        return $this->Store;
    }

    function loadStore(){

        $this->getItems();
        if(!count($this->items)){
            return false;
        }

        foreach($this->items as $item){
            break;
        }

        $Store = new \Model\Store($item['storeId']);
        return $Store && $Store instanceof \Model\Store ? $Store : false;
    }

    function getFormatedCreationDate(){
        return utf8fix(strftime("%d %b %Y&nbsp;&nbsp;&nbsp;%H:%M", strtotime($this->created)));
    }

    function canBeClosedByBuyer(){
        return (bool)$this->payed;
    }

    function canBeCanceledByStore(){
        return $this->status == 21;
    }

    function canBeConfirmedBySeller(){
        return $this->status == 21;
    }

    function Buyer(){

        if($this->_Buyer === null){
            $this->_Buyer = new \Verba\Mod\User\Model\User($this->owner);
        }

        return $this->_Buyer;
    }

    function getBillTitle(){
        if($this->__billTitle === null){
            $this->__billTitle = \Verba\Lang::get('order invoiceText', array('invCode' =>  $this->code));
        }
        return $this->__billTitle;
    }

    function setPrice_map($val){
        if(!is_string($val) || !strlen($val)){
            return null;
        }

        $this->{$this->_confPropName}['price_map'] = json_decode($val, true);
    }

    function getBalPers(){
        if(!is_array($this->{$this->_confPropName}['price_map'])
            || !array_key_exists('balPers', $this->{$this->_confPropName}['price_map'])
            || $this->{$this->_confPropName}['price_map']['balPers'] <= 0)
        {
            return 0;
        }

        return $this->{$this->_confPropName}['price_map']['balPers'];
    }

    function getByuerSum(){

        if($this->__sumToBalance === null){

            $this->__sumToBalance = $this->currency->round($this->getItemsTotal() * $this->{$this->_confPropName}['price_map']['balPers']);

        }

        return $this->__sumToBalance;
    }

    function getSellerSum(){

        if($this->__sumToSeller === null){
            $val = $this->getItemsTotal();
            $oCur = $this->getOCur();
            if($oCur->getId() != $this->currency->getId()){
                $price_map = $this->price_map;
                $crossrate = isset($price_map['crossrate'])
                    ? $price_map['crossrate']
                    : 0;
                $val = $val * $crossrate;
            }

            $this->__sumToSeller = $oCur->round($val);
        }

        return $this->__sumToSeller;
    }

    function getOCur(){

        if($this->__oCur === null){
            $this->__oCur =  \Verba\Mod\Currency::getInstance()->getCurrency($this->{$this->_confPropName}['price_map']['oCurId']);
        }

        return $this->__oCur;
    }

    function gatewayPaymentSum(){
        return $this->currency->round($this->getItemsTotal() * $this->price_map['Pck']);
    }
}