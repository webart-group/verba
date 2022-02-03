<?php
namespace Mod\Cart;


class Item extends \Verba\Base {

    protected $hash;
    protected $id;
    protected $oh;
    protected $tform = array(
        'ot_id' => null,
        'id' => null,
        'data' => null
    );
    protected $promos = array();
    protected $_extra = array();
    protected $_props = array(
        'id' => null,
        'ot_id' => null,
        'quantity' => null,
        'price' => null,
        'title' => '',
        'parentId' => null,
        'image' => null,
        'storeId' => null,
        'currencyId' => null,
        'catalogId' => null,
        //'customerStatusPrice' => null,
        //'allCustomerStatusPrices' => null,
        //'descriptionTagsFree' => '',
        'description' => '',
    );
    /**
     * @var \Mod\Cart\CartInstance
     */
    protected $cart;
    /**
     * @var \Model\Store
     */
    protected $_Store;
    protected $_raw_props;
    /**
     * @var \Model\Item
     */
    protected $_Catalog;

    function __get($propName){
        $mtd = 'get'.ucfirst($propName);
        if(method_exists($this, $mtd)){
            return $this->$mtd();
        }elseif(array_key_exists($propName, $this->_props)){
            return $this->_props[$propName];
        }
        return;
    }

    function __set($propName, $val){
        $mtd = 'set'.ucfirst($propName);
        if(method_exists($this, $mtd)){
            return $this->$mtd($val);
        }elseif(array_key_exists($propName,$this->_props)){
            $this->_props[$propName] = $val;
            return $this->_props[$propName];
        }
        return;
    }

    function __construct($cart, $hash, $props){
        $this->cart = $cart;
        $this->oh = \Verba\_oh($props['ot_id']);
        $this->ot_id = $this->oh->getCode();
        $this->setHash($hash);

        $this->_raw_props = $props;

        unset($props['hash']);
        $this->setId($props[$this->oh->getPAC()]);
        unset($props[$this->oh->getPAC()]);

        if(isset($props['_tform'])){
            $this->setTform($props['_tform']);
            unset($props['_tform']);
        }

        if(is_array($props) && count($props)){
            foreach($props as $k => $v){
                $this->$k = $v;
            }
        }

    }

    function getStore(){
        if($this->_Store === null){
            $this->_Store = \Verba\_mod('store')->OTIC()->getItem($this->_props['storeId']);
            if(!is_object($this->_Store)){
                $this->_Store = false;
            }
        }
        return $this->_Store;
    }

    function exportToArray(){
        return array(
            'ot_id' => $this->oh->getID(),
            'id' => $this->getId(),
            'quantity' => $this->getQuantity(),
            'catalogId' => $this->getCatalogId(),
            '_extra' => $this->getExtra(),
            '_tform' => $this->getTform(),
            'promos' => $this->getPromos(),
        );
    }

    function loadPromotions(){
        $d = \Verba\_mod('promotion')->loadPromosByGoods($this->cart, $this->oh->getId(), $this->getId());
        if(!is_array($d) || empty($d)){
            return false;
        }
        $this->promos = $this->cart->addPromos($d, $this);
        if(!$this->promos){
            $this->promos = array();
        }
        return $this->promos;
    }

    function refresh(){
        $this->refreshPromos();
    }

    function refreshPromos(){
        if(!is_array($this->promos) || empty($this->promos)){
            return;
        }
        foreach($this->promos as $did => $Discount){
            $Discount->refresh();
        }
    }

    function recount(){
        $this->recountPromos();
    }

    function recountPromos(){
        if(!is_array($this->promos) || empty($this->promos)){
            return;
        }
        foreach($this->promos as $did => $Discount){
            $Discount->recount();
        }
    }

    /**
     * Returns promos array by specified affect
     *
     * @param string $affect order | goods
     */
    function getPromosByAffect($affect){

        if(!is_string($affect)){
            return false;
        }

        $r = array();
        foreach($this->promos as $did => $Discount){
            if($affect != $Discount->affect){
                continue;
            }
            $r[$did] = $Discount;
        }
        return $r;
    }

    function getPromos(){
        return $this->promos;
    }

    function prepareToRemove(){
        if(empty($this->promos)){
            return true;
        }
        foreach($this->promos as $did => $Discount){
            if(!$Discount instanceof \Mod\Order\Discount\Cart\FirstPurchase){
                continue;
            }
            $Discount->unlinkGoods($this);
        }
    }

    function getOh(){
        return $this->oh;
    }

    function getOtId(){
        return $this->oh->getID();
    }

    function packToClient(){
        $r = array($this->hash => $this->_props);
        $r[$this->hash]['id'] = $this->id;
        $r[$this->hash]['hash'] = $this->hash;
        $r[$this->hash]['discounts'] = array();
        if(!empty($this->promos)){
            foreach($this->promos as $did => $Discount){
                $r[$this->hash]['discounts'][$did] = $Discount->packToCart();
            }
        }
        $r[$this->hash]['_extra'] = $this->_extra;

        return $r;
    }

    function quantityUpdate($val){
        $val = (int)$val;
        if($val < 0){
            return false;
        }
        if($val == $this->getQuantity()){
            return $val;
        }
        $r = $this->execQuantityUpdateQuery($val);
        if(is_int($r)){
            $this->setQuantity($val);
        }
        return $val;
    }

    protected function execQuantityUpdateQuery($val){
        $_cst = \Verba\_oh('customer');
        $q = "UPDATE ".$_cst->vltURI($this->oh)."
    SET `quantity` = '".$val."'
    WHERE `p_ot_id` = '".$_cst->getID()."'
    && `p_iid` = '".$this->cart->getCustomerId()."'
    && `ch_ot_id` = '".$this->ot_id."'
    && `ch_iid` = '".$this->id."'
    && `hash` = '".$this->hash."'";
        $sqlr = $this->DB()->query($q);
        if(!$sqlr || !$sqlr->getAffectedRows()){
            throw new \Exception('Bad operation');
        }
        return $sqlr->getAffectedRows();
    }

    function setDescription($val){
        $this->_props['description'] = $val;
        //$this->setDescriptionTagsFree($val);
        return $this->_props['description'];
    }

    function getDescription(){
        return $this->_props['description'];
    }

    function setDescriptionTagsFree($val){
        $this->_props['descriptionTagsFree'] = strip_tags($val);
        return $this->_props['descriptionTagsFree'];
    }

    function getDescriptionTagsFree(){
        return $this->_props['descriptionTagsFree'];
    }

    function setTitle($val){
        $this->_props['title'] = $val;
    }

    function getTitle(){
        return $this->_props['title'];
    }

    function setId($val){
        $this->id = (int)$val;
        $this->_props['id'] = $this->id;
    }

    function getId(){
        return $this->id;
    }

    function setHash($val){
        $this->hash = (string)$val;
    }
    function getHash(){
        return $this->hash;
    }

    function setPrice($val){
        $this->_props['price'] = (float)$val;
    }
    function getPrice(){
        return $this->_props['price'];
    }
    function getFinalPrice(){
        $price = $this->getCustomerPrice();
        $promos = $this->getPromosByAffect('goods');
        foreach($promos as $pid => $Discount){
            $price = $Discount->applyTo($price);
        }
        $paysysId = $this->cart->getPaysysId();
        if(is_numeric($paysysId) && ($Store = $this->getStore())){
            $curr = $this->cart->getCurrency();
            $price = $Store->calcPriceForBuyer($price, $curr, $paysysId);
        }

        return $price;
    }

    function getCustomerPrice(){
        if(isset($this->_props['customerStatusPrice'])
            && $this->_props['customerStatusPrice'] > 0){
            $r = $this->_props['customerStatusPrice'];
        }else{
            $r = $this->_props['price'];
        }

        return $r;
    }

    function getFinalDiscount(){
        $price = $this->getPrice();
        $final_price = $this->getFinalPrice();
        $k = $price > $final_price ? -1 : 1;
        return ($price - $final_price) * $k;
    }

    function getFinalDiscountPercent(){
        $price = $this->getPrice();
        $final_price = $this->getFinalPrice();

        $k = $price > $final_price ? -1 : 1;

        $r = (100 - ($final_price / $price * 100)) * $k;
        return $r;
    }

    function setCustomerStatusPrice($val){
        $this->_props['customerStatusPrice'] = (float)$val;
    }
    function getCustomerStatusPrice(){
        return isset($this->_props['customerStatusPrice'])
            ? $this->_props['customerStatusPrice']
            : null;
    }
    function getCustomerStatusDiscount($customerStatusId = null){

        $p = $this->getPrice();
        if($customerStatusId !== null){
            $customerStatusId = (int)$customerStatusId;
            if(isset($this->_props['allCustomerStatusPrices'])
                && is_array($this->_props['allCustomerStatusPrices'])
                && array_key_exists($customerStatusId, $this->_props['allCustomerStatusPrices'])){
                $cp = $this->_props['allCustomerStatusPrices'][$customerStatusId]['price'];
            }else{
                $cp = false;
            }
        }else{
            $cp = $this->getCustomerStatusPrice();
        }

        if(!isset($cp)
            || $cp <= 0
            || $cp == $p){
            return 0;
        }
        return $p - $cp;
    }

    function setQuantity($val){
        $val = (int)$val;
        if($val < 0){
            return false;
        }
        $this->_props['quantity'] = $val;
        return $this->_props['quantity'];
    }
    function getQuantity(){
        return $this->_props['quantity'];
    }

    function setExtra($val){
        $this->_extra = !empty($val) ? (array)json_decode($val) : '';
    }
    function getExtra(){
        return $this->_extra;
    }

    function getCurrencyId(){
        return $this->_props['currencyId'];
    }
    function getCurrency(){
        return \Verba\_mod('currency')->getCurrency($this->_props['currencyId']);
    }

    /**
     * @param $val array вид
     * array(
     *  'ot_id' => tfrom_ot_id
     *  'id' => tfrom_iid
     *  'data' => tformData
     * )
     */
    function setTform($val){
        if(!is_array($val) || !isset($val['ot_id'])
            || !\Verba\isOt($val['ot_id'])
            || !($_tform = \Verba\_oh($val['ot_id']))
            || !$_tform instanceof \Model\Tform
            || !settype($val['id'], 'integer')
            || !$val['id']
        ){
            return false;
        }
        $this->tform['ot_id'] = $_tform->getID();
        $this->tform['id'] = $val['id'];
        if(isset($val['data']) && is_array($val['data'])){
            $this->tform['data'] = $val['data'];
        }
    }
    function getTform(){
        return $this->tform;
    }
    function getTformOtId(){
        return $this->tform['ot_id'];
    }
    function getTformId(){
        return $this->tform['id'];
    }
    function getTformData(){
        return $this->tform['data'];
    }

    function setCatalogId($val){
        if(is_numeric($val)){
            $this->_props['catalogId'] = (int)$val;
        }
    }

    function getCatalogId(){

        return $this->_props['catalogId'];

    }

    function getCatalog(){
        if($this->_Catalog === null){

            if(!$this->_props['catalogId'] ||
                !is_object($this->_Catalog = \Verba\_mod('catalog')->OTIC()->getItem($this->_props['catalogId']))
            ){
                $this->_Catalog = false;
            }
        }
        return $this->_Catalog;
    }
}
