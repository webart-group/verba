<?php

namespace Verba\Mod\Cart;

use Verba\ObjectType\Attribute\Predefined;

class CartInstance extends \Verba\Base
{
    protected $id;
    protected $cstId;

    protected $topay;
    protected $total;
    protected $discount;
    protected $shipping;

    protected $promos = array();
    protected $created = 0;
    protected $active;
    protected $shippingCost;
    protected $shippingFree;
    protected $shippingDelta;

    protected $items;
    /**
     * @var \Verba\Model\Currency
     */
    protected $currency;
    /**
     * @var int
     */
    protected $currencyId;
    protected $paysys;
    protected $dbExists = false;
    /**
     * @var \Verba\Mod\Customer\Profile
     */
    protected $cp;

    function __construct($customerProfile, $loadItems = true)
    {
        if (!$customerProfile instanceof \Verba\Mod\Customer\Profile) {
            return false;
        }
        $this->cp = $customerProfile;
        $this->cstId = $this->cp->getId();
        $this->id = md5($this->cstId . 'cart');
        $this->loadProps();

        if ($loadItems) {
            $this->items = $this->loadItems($this->cstId);
        }

        if (!is_array($this->items)) {
            $this->items = array();
        }

        $mOrder = \Verba\_mod('order');
        $this->shippingCost = $mOrder->gC('shipping');
        $this->shippingFree = $mOrder->gC('shipping_free');

        $this->loadGlobalDsicounts();
    }

    function __call($mthd, $args)
    {
        $delegatedMthd = false;
        if (method_exists($this, '_rc_' . $mthd)) {
            $delegatedMthd = 'getSomeRecountedValue';
            $args = array('_rc_' . $mthd, $args);
        }

        if (!$delegatedMthd) {
            throw new \Exception('Call undefined method \'' . var_export($mthd, true) . '\'');
        }

        return call_user_func_array(array($this, $delegatedMthd), $args);
    }

    function __sleep()
    {
        $this->currencyId = $this->currency->getId();
        $props = get_object_vars($this);
        unset($props['currency']);
        return array_keys($props);
    }

    function __wakeup()
    {
        $this->setCurrency($this->currencyId);
    }

    function refreshCustomerProfile($profile)
    {
        if (!$profile instanceof \Verba\Mod\Customer\Profile) {
            throw new \Exception('Unable to refresh Cart \Verba\Mod\Customer\Profile - wrong type.');
        }
        $this->cp = $profile;
        return $this->cp;
    }

    function setPaysys($ps)
    {
        if (!is_object($ps)) {
            $ps = \Verba\_mod('payment')->getPaysys($ps);
        }
        if (!is_object($ps)) {
            return false;
        }
        $this->paysys = $ps;
    }

    function getPaysysId()
    {
        return is_object($this->paysys) ? $this->paysys->getId() : false;
    }

    /**
     * @return \Verba\Mod\Customer\Profile
     */
    function getProfile()
    {
        return $this->cp;
    }

    function getCustomerId()
    {
        return $this->cstId;
    }

    protected function loadItems($cstId)
    {

        $_cst = \Verba\_oh('customer');
        $_product = \Verba\_oh('product');

        $sqlr = $this->DB()->query(
            "SELECT *, ch_ot_id as `ot_id` FROM " . $_cst->vltURI($_product) . "
WHERE p_ot_id = '" . $_cst->getID() . "' && p_iid = '" . $cstId . "'");

        if (!$sqlr || !$sqlr->getNumRows()) {
            return array();
        }
        $l2 = array();
        $r = array();
        while ($row = $sqlr->fetchRow()) {
            if (!isset($oh) || $row['ot_id'] != $oh->getID()) {
                $oh = \Verba\_oh($row['ot_id']);
            }
            $row[$oh->getPAC()] = $row['ch_iid'];
            $r[$row['hash']] = $row;
            if (!isset($l2[$row['ot_id']][$row[$oh->getPAC()]])) {
                $l2[$row['ot_id']][$row[$oh->getPAC()]] = array();
            }
            $l2[$row['ot_id']][$row[$oh->getPAC()]][] = $row['hash'];
        }

        $this->updateItemsCustomData($l2, $r);
        return $r;
    }

    protected function refreshItems()
    {
        $this->items = $this->loadItems($this->cstId);
    }

    protected function loadProps()
    {
        $sqlr = $this->DB()->query(
            "SELECT * FROM `" . SYS_DATABASE . "`.`cart` WHERE id = '" . $this->id . "' && customerId = '" . $this->cstId . "'");
        if ($sqlr && $sqlr->getNumRows()) {
            $this->dbExists = true;
            $row = $sqlr->fetchRow();
        }

        if (isset($row) && is_array($row) && array_key_exists('currencyId', $row)) {
            $this->setCurrency($row['currencyId']);
        } else {
            $this->setCurrency(false);
        }

        return;
    }

    function setCurrency($currency)
    {

        if (!is_object($currency) && (is_string($currency) || is_numeric($currency))) {
            $currency =  \Verba\Mod\Currency::getInstance()->getCurrency($currency, true);
        }

        if (!$currency instanceof \Verba\Model\Currency
            || !$currency->active
            || $currency->hidden) {
            $currency = false;
        }

        if (!$currency) {
            if (is_object($this->currency) && $this->currency instanceof \Verba\Model\Currency) {
                return false;
            }

            $currency =  \Verba\Mod\Currency::getInstance()->getBaseCurrency();
            if (!$currency
                || !$currency->active
                || $currency->hidden
            ) {
                $all_active_visible_curs =  \Verba\Mod\Currency::getInstance()->getCurrency(false, true, true);
                if (is_array($all_active_visible_curs) && count($all_active_visible_curs)) {
                    $currency = current($all_active_visible_curs);
                }
            }
        }

        if (!is_object($currency)
            || !$currency instanceof \Verba\Model\Currency
            || !$currency->active
            || $currency->hidden) {
            throw new \Exception('One active curency required at least.');
        }

        $this->currency = $currency;
        $this->currencyId = $this->currency->getId();

        return $this->currency;
    }

    protected function extractAndLoadItemFromEnv($item = null)
    {
        if (is_array($item) && isset($item['id']) && isset($item['ot_id'])) {
            $rq = $item;
        } elseif (isset($_REQUEST['item'])) {
            $rq = $_REQUEST['item'];
        } else {
            $rq = $_REQUEST;
        }

        if (!is_array($rq)) {
            throw new \Exception('Cant find Item Data into Request');
        }
        if (!isset($rq['id'])) {
            throw new \Exception('Invalid object ID');
        }
        if (!isset($rq['ot_id'])) {
            throw new \Exception('Invalid object OT');
        }

        $_prod = \Verba\_oh($rq['ot_id']);
        $_cst = \Verba\_oh('customer');
        $_catalog = \Verba\_oh('catalog');

        if (!$_cst->inChilds($_prod)) {
            throw new \Exception('Object can not be placed in the cart');
        }
        $qm = new \Verba\QueryMaker($_prod, false, true);
        $qm->addWhere(1, 'active', 'active');
        $qm->addWhere($rq['id'], 'iid', $_prod->getPAC());
        $q = $qm->getQuery();
        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getNumRows()) {
            $this->log()->secure('Try to get unexists or inactive Product Item');
            throw new \Exception('Invalid Product Item');
        }
        $dbItem = $sqlr->fetchRow();
        $prodItem = $_prod->initItem($dbItem);

        $formedRq = array(
            'ot_id' => $_prod->getID(),
            'id' => $prodItem->getId(),
            'storeId' => $prodItem->storeId,
            '_extra' => array(
                'info' => array(),
                'tform' => array(),
                'data' => array(),
            ),
            'quantity' => 1,
        );

        if (isset($rq['catalogId']) && $rq['catalogId']) {
            /**
             * @var $mCatalog \Catalog
             */
            $mCatalog = \Verba\_mod('catalog');
            $catItem = $mCatalog->OTIC()->getItem($rq['catalogId']);
            if ($catItem && $catItem->getId() == $prodItem->serviceCatId) {
                $formedRq['catalogId'] = $catItem->getId();
                $catCfg = $catItem->getValue('config');
            }
        }
        /**
         * @var ProductType_product
         */
        $Ph = \Verba\_mod('product')->getProductHandler($_prod);

        // Получение из запроса данных по tform
        // создание записи tform
        // включение данных tform в данные по товару

        $tformId = null;
        $tformData = null;
        $_tform = null;
        if (isset($rq['_tform'])
            && isset($rq['_tform']['ot_id'])
            && \Verba\isOt($rq['_tform']['ot_id']) && ($_tform = \Verba\_oh($rq['_tform']['ot_id']))
            && $_tform instanceof \Model\Tform
        ) {
            // если tform уже записана в БД и id известен
            // просто копируем $tformId
            if (isset($rq['_tform']['id'])
                && is_numeric($rq['_tform']['id'])
                && $rq['_tform']['id'] > 0) {
                $tformId = $rq['_tform']['id'];
                $tformData = isset($rq['_tform']['data'])
                && is_array($rq['_tform']['data'])
                && !empty($rq['_tform']['data'])
                    ? $rq['_tform']['data']
                    : false;
                // если id отсутствует но есть данные для tform
            } elseif (isset($rq['_tform']['data'])
                && is_array($rq['_tform']['data'])
                && !empty($rq['_tform']['data'])) {
                $ae_tfrom = $_tform->initAddEdit();
                $ae_tfrom->setGettedObjectData($rq['_tform']['data']);
                $ae_tfrom->addParents($formedRq['ot_id'], $formedRq['id']);
                $ae_tfrom->addExtendedData(array('prodItem' => $prodItem));
                $tformId = $ae_tfrom->addedit_object();
                $tformData = $ae_tfrom->getActualData();
            }

            $formedRq['_tform'] = array(
                'ot_id' => $_tform->getID(),
                'id' => $tformId,
                'data' => is_array($tformData) && count($tformData) ? $tformData : false,
            );
        }

        // Обработка и наполнение данными массива info-полей
        // родными полями продукта
        $infoF = array();
        if (isset($catCfg) && is_array($catCfg) && isset($catCfg['groups']['order_info']['items'])
            && is_array($catCfg['groups']['order_info']['items'])
        ) {
            foreach ($catCfg['groups']['order_info']['items'] as $fi => $fiData) {
                $infoF[] = $fiData['code'];
            }
        }
        if (count($infoF)) {
            $this->fillExtraFields($formedRq['_extra']['info'], $infoF, $_prod, $rq, $dbItem, $prodItem);
        }
        // возможными полями из tfrom
        if ($tformId) {
            $infoF = array();
            if (isset($catCfg) && is_array($catCfg) && isset($catCfg['groups']['tform']['items'])
                && is_array($catCfg['groups']['tform']['items'])
            ) {
                foreach ($catCfg['groups']['tform']['items'] as $fi => $fiData) {
                    if (isset($fiData['showInOrder']) && !$fiData['showInOrder']) {
                        continue;
                    }
                    $infoF[] = $fiData['code'];
                }
            }

            if (count($infoF)) {
                $this->fillExtraFields($formedRq['_extra']['tform'], $infoF, $_tform, $rq, $tformData);
            }
        }

        // making hash
        if (isset($rq['hash']) && is_string($rq['hash'])) {
            $formedRq['hash'] = $rq['hash'];
        } else {
            $formedRq['hash'] = $Ph->generateItemHash($rq, $prodItem);
        }
        if (!$formedRq['hash']) {
            throw new \Exception('Invalid object Hash');
        }

        if (isset($rq['quantity']) && ($q = intval($rq['quantity'])) > 1) {
            $formedRq['quantity'] = $q;
        }

        return array($formedRq, $dbItem);
    }

    /**
     * @param $fillTo
     * @param $fields
     * @param $_oh \Verba\Model
     * @param $rq array
     * @param $data array
     * @param $Item \Verba\Model\Item
     */

    protected function fillExtraFields(&$fillTo, $fields, $_oh, $rq, $data, $Item = false)
    {
        if (!is_array($fields) || !count($fields)) {
            return;
        }

        foreach ($fields as $cEf) {

            $val = isset($rq['_extra'][$cEf])
                ? $rq['_extra'][$cEf]
                : (is_object($Item) ? $Item->getValue($cEf) : $data[$cEf]);

            $__valKey = $cEf . '__value';
            $__val = null;


            /**
             * @var $A Predefined
             */
            if (is_object($A = $_oh->A($cEf))) {

                if ($A->isPredefined()) {

                    $pdvs = $A->filterValues(array(
                        'id' => $val,
                    ));
                    $__val = isset($pdvs[$val])
                        ? $pdvs[$val]
                        : null;
                }

                if ($A->isForeignId()) {
                    $__val = isset($data[$__valKey])
                        ? $data[$__valKey]
                        : null;
                }

            }

            ASSIGN:

            if (!$val) {
                continue;
            }

            $fillTo[$cEf] = $val;
            if (isset($__val)) {
                $fillTo[$__valKey] = $__val;
            }

        }
    }

    function itemInCart($hash)
    {
        return isset($this->items[$hash]);
    }

    function getItemsCount()
    {
        return is_array($this->items)
            ? count($this->items)
            : 0;
    }

    function getItems($hashes = null)
    {
        if ($hashes === null) {
            return $this->items;
        }
        if (is_string($hashes) && array_key_exists($hashes, $this->items)) {
            return $this->items[$hashes];
        }

        if (!is_array($hashes)) return false;

        $r = array();
        foreach ($hashes as $hash) {
            if (!array_key_exists($hash, $this->items)) {
                continue;
            }
            $r[$hash] = $this->items[$hash];
        }
        return $r;
    }

    function getItem($hash)
    {
        return array_key_exists($hash, $this->items)
            ? $this->items[$hash]
            : null;
    }

    function addItem($bp = null)
    {
        $_cst = \Verba\_oh('customer');

        if (!$this->cstId) {
            throw new \Exception('Cant find Customer Profile');
        }
        list($formedRq, $dbItem) = $this->extractAndLoadItemFromEnv($bp);
        $_product = \Verba\_oh($dbItem['ot_id']);
        $hash = $formedRq['hash'];

        if ($this->itemInCart($hash)) {
            $cq = $this->items[$hash]->quantity;
            $formedRq['quantity'] += $cq;
            return $this->itemQuantityUpdate($formedRq);
        }

        $dbItem['_extra'] = $formedRq['_extra'];
        $dbItem['quantity'] = $formedRq['quantity'];
        $dbItem['_tform'] = $formedRq['_tform'];
        $r = array($hash => $dbItem);
        $l2 = array($dbItem['ot_id'] => array($dbItem[$_product->getPAC()] => array($hash)));
        $this->updateItemsCustomData($l2, $r);

        if (is_callable(array($r[$hash], 'getQuantityAvaible'))
            && $r[$hash]->getQuantityAvaible() < $formedRq['quantity']) {
            throw new \Exception('Item quantity is unavaible.');
        }
        $itemExtra = $r[$hash]->getExtra();
        if (is_array($itemExtra) && !empty($itemExtra)) {
            $extraStm = $this->DB()->escape_string(json_encode($itemExtra));
        } else {
            $extraStm = '';
        }

        $tformOtId = (string)$r[$hash]->getTformOtId();
        $tformId = (string)$r[$hash]->getTformId();

        $Store = $r[$hash]->getStore();

        $sqlr = $this->DB()->query("INSERT INTO " . $_cst->vltURI($_product) . " (
    `storeId`,
    `p_ot_id`,
    `p_iid`,
    `ch_ot_id`,
    `ch_iid`,
    `quantity`,
    `price`,
    `currencyId`,
    `created`,
    `extra`,
    `tformOtId`,
    `tformId`,
    `hash`
    ) VALUES (
    '" . $Store->getId() . "',
    '" . $_cst->getID() . "',
    '" . $this->cstId . "',
    '" . $r[$hash]->getOtId() . "',
    '" . $r[$hash]->getId() . "',
    '" . $r[$hash]->getQuantity() . "',
    '" . $r[$hash]->getPrice() . "',
    '" . $r[$hash]->getCurrencyId() . "',
    '" . strftime('%Y-%m-%d %H:%M:%S', time()) . "',
    '" . $extraStm . "',
    '" . $tformOtId . "',
    '" . $tformId . "',
    '" . $hash . "'
    )");
        if (!$sqlr || !$sqlr->getAffectedRows()) {
            throw new \Exception('Bad operatoin');
        }
        $this->items[$hash] = $r[$hash];

        // load Promotions
        $this->items[$hash]->loadPromotions();

        $this->items[$hash]->refresh();
        return $this->getItems($hash);
    }

    function reset()
    {
        $this->discount = null;
        $this->shipping = null;
        $this->shippingDelta = null;
        $this->total = null;
        $this->topay = null;
    }

    function recount()
    {
        if (!empty($this->items)) {
            foreach ($this->items as $hash => $Item) {
                $Item->recount();
            }
        }
        $this->recountTotal();
        $this->recountDiscount();
        $this->recountShipping();
        $this->recountTopay();
    }

    function refresh()
    {
        $this->reset();
        $this->checkPromosIsApplicaple();

        if (!empty($this->items)) {
            foreach ($this->items as $hash => $Item) {
                $Item->refresh();
            }
        }

        $this->recount();

    }

    function clearItems()
    {
        $_cst = \Verba\_oh('customer');
        $_product = \Verba\_oh('product');

        $sqlr = $this->DB()->query("DELETE FROM " . $_cst->vltURI($_product) . "
WHERE `p_ot_id` = '" . $_cst->getID() . "'
&& `p_iid` = '" . $this->cstId . "'
");
        if (!$sqlr) {
            throw new \Exception('Bad operation');
        }
        $this->items = array();
    }

    function resetAndClearItems()
    {
        $this->clearItems();
        $this->refresh();
    }

    function loadGlobalDsicounts()
    {
        if (!$this->cstId) {
            throw new \Exception('Cant find Customer Profile');
        }
        $gdsc = \Verba\_mod('promotion')->loadGlobalPromos($this);

        if (!is_array($gdsc)) {
            return false;
        }
        $this->addPromos($gdsc);
        return $gdsc;
    }

    function addPromos($discounts, $cartItem = false)
    {
        if (!is_array($discounts) || empty($discounts)) {
            return null;
        }
        $r = array();
        foreach ($discounts as $did => $dsc) {
            $d = $this->addPromo($did, $dsc, $cartItem);
            if (!is_object($dsc)) {
                continue;
            }
            $r[$did] = $d;
        }
        return $r;
    }

    function addPromo($id, $Promo, $cartItem = false)
    {

        if (!$Promo instanceof \Verba\Mod\Order\Discount) {
            return false;
        }

        if (!array_key_exists($id, $this->promos)) {
            if (!is_string($Promo->context) || empty($Promo->context)) {
                $Promo->context = 'global';
            }
            if ($Promo instanceof \Verba\Mod\Order\Discount\Cart\Item) {
                $Promo->addCartItem($cartItem);
            }
            $this->promos[$id] = $Promo;
        }

        return $this->promos[$id];
    }

    function getPromo($id)
    {
        if (!array_key_exists($id, $this->promos)) {
            return null;
        }

        if (!$this->promos[$id] instanceof \Verba\Mod\Order\Discount) {
            return false;
        }

        return $this->promos[$id];
    }

    function checkPromosIsApplicaple()
    {
        if (empty($this->promos)) {
            return;
        }

        foreach ($this->promos as $did => $Discount) {
            if (!$Discount->isApplicable()) {
                $this->removePromo($did);
            }
        }
    }

    function getPromos($ctx = null)
    {
        if (empty($this->promos)) {
            return $this->promos;
        }

        if ($ctx) {
            $r = array();
            foreach ($this->promos as $did => $Discount) {
                if ($ctx != $Discount->context) {
                    continue;
                }
                $r[$did] = $Discount;
            }
            return $r;
        }

        return $this->promos;
    }

    /**
     * Returns promos array by specified affect
     *
     * @param string $affect order | goods
     */
    function getPromosByAffect($affect)
    {

        if (!is_string($affect)) {
            return false;
        }

        $r = array();
        foreach ($this->promos as $did => $Discount) {
            if ($affect != $Discount->affect) {
                continue;
            }
            $r[$did] = $Discount;
        }
        return $r;
    }

    function removePromo($id)
    {
        if (!array_key_exists($id, $this->promos)
            || !$this->promos[$id] instanceof \Verba\Mod\Order\Discount) {
            return false;
        }
        unset($this->promos[$id]);
    }

    function itemQuantityUpdate($bp = null)
    {
        list($formedRq, $dbItem) = $this->extractAndLoadItemFromEnv($bp);
        $hash = $formedRq['hash'];
        if (!$this->itemInCart($hash)) {
            $this->addItem($formedRq);
        }
        $Item = $this->getItems($hash);
        if (!$Item->quantityUpdate($formedRq['quantity'])) {
            throw new \Exception('Item quantity is unavaible');
        }
        return $this->getItems($hash);
    }

    function itemDelete($hash)
    {
        $_cst = \Verba\_oh('customer');
        $hash = (string)$hash;
        if (!$this->itemInCart($hash)
            || !($Item = $this->getItems($hash)) instanceof \Verba\Mod\Cart\Item) {
            throw new \Exception('Cart Item not found');
        }
        $_product = $Item->getOh();
        $Item->prepareToRemove();
        $sqlr = $this->DB()->query("DELETE FROM " . $_cst->vltURI($_product) . "
    WHERE `p_ot_id` = '" . $_cst->getID() . "'
    && `p_iid` = '" . $this->DB()->escape_string($this->cstId) . "'
    && `hash` = '" . $this->DB()->escape_string($Item->hash) . "'
    LIMIT 1");
        if (!$sqlr || !$sqlr->getAffectedRows()) {
            throw new \Exception('Bad operatoin');
        }
        unset($this->items[$hash]);
        return true;
    }

    function checkItemsAvaibility($confirmItems)
    {
        $r = array();
        if (!is_array($confirmItems) || empty($confirmItems)) {
            return $r;
        }
        $this->refreshItems();
        $items = $this->getItems();

        $itersect = array_intersect_key($confirmItems, $items);
        if (empty($itersect)) {
            return $r;
        }

        foreach ($itersect as $hash => $reqQuantity) {
            $items[$hash]->quantityUpdate($reqQuantity);
            $r[$hash] = array('avaible' => $items[$hash]->getQuantity());
        }
        return $r;
    }

    function updateItemsCustomData(&$ots, &$items)
    {
        $mProduct = \Verba\_mod('product');
        foreach ($ots as $ot => $iidsHashes) {
            $oh = \Verba\_oh($ot);
            $oh_code = $oh->getCode();

            $Ph = $mProduct->getProductHandler($oh_code);
            $Ph->prepareToCart($this, $oh, $iidsHashes, $items);
        }
    }

    function getCurrency()
    {
        return $this->currency;
    }

    function getCurrencyId()
    {
        return $this->currency->getId();
    }

    function packCurrencyToClient()
    {
        return $this->currency->packToCart();
    }

    function currencyChange($currencyId)
    {

        $existsCur = $this->getCurrency();
        $newCur = $this->setCurrency($currencyId);

        if (!$newCur || $newCur === $existsCur) {
            return false;
        }

        if (!$this->updateCartProps(array(
            'currencyId' => $newCur->getId(),
        ))) {
            $this->log()->error('Error while update actual Cart\'s Currency ID');
        }

        return $newCur;
    }

    function updateCartProps($props)
    {
        if (!$this->dbExists && !$this->createCartIntoDb()) {
            return false;
        }
        if (!is_array($props) || empty($props)) {
            return false;
        }
        $str = '';
        foreach ($props as $field => $value) {
            $str .= ", `" . $this->DB()->escape_string($field) . "` = '" . $this->DB()->escape_string($value) . "'";
        }
        $str = mb_substr($str, 1);
        if (!$str) {
            return false;
        }
        $q = "UPDATE `" . SYS_DATABASE . "`.`cart` SET " . $str
            . " WHERE `id` = '" . $this->id . "' && `customerId` = '" . $this->cstId . "' LIMIT 1";

        $this->DB()->query($q);
        $sqlr = $this->DB()->query($q);
        if (!$sqlr) {
            $this->log()->error("Unable to update cart Props at DB. Props:[" . var_export($props, true) . "]. \n" . serialize($this));
            return false;
        }

        return true;
    }

    function createCartIntoDb()
    {
        $q = "INSERT INTO `" . SYS_DATABASE . "`.`cart` ("
            . "`id`"
            . ", `customerId`"
            //.", `paysysId`"
            . ", `currencyId`"
            . ", `created`"
            . ", `owner`"
            . ", `ip`"
            . ") VALUES ("
            . "'" . $this->id . "'"
            . ",'" . $this->cstId . "'"
            //.",'".$this->paysys->id."'"
            . ",'" . $this->currency->getId() . "'"
            . ",'" . strftime("%Y-%m-%d %H:%M:%S") . "'"
            . ",'" . \Verba\User()->getID() . "'"
            . ",'" . ip2long(\Verba\getClientIP()) . "'"
            . ")";
        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getAffectedRows()) {
            return false;
        }
        $this->dbExists = true;
        return true;
    }

    function packToCfg()
    {
        $mCustomer = \Verba\_mod('customer');

        if (is_object($this->cp)) {
            $cusData = $this->cp->packToCart();
        } else {
            $cusData = null;
        }

        return array(
            'items' => $this->packItems(),
            'url' => \Verba\_mod('Cart')->gC('url'),
            'customer' => $cusData,
            'currency' => $this->packCurrencyToClient(),
            'customerStatuses' => $mCustomer->getCustomerStatuses('cart'),
            'shipping' => $this->shipping,
            'shippingFree' => $this->shippingFree,
            'minimal_order_amount' => \Verba\_mod('order')->gC('minimal_order_amount'),
            'discounts' => $this->packDiscounts(),
        );
    }

    function packItems()
    {
        $r = array();
        if (!$this->items) {
            return $r;
        }
        foreach ($this->items as $hash => $item) {
            $packed = $item->packToClient();
            $r[$hash] = $packed[$hash];
        }
        return $r;
    }

    function packDiscounts()
    {
        if (!is_array($this->promos)) {
            return false;
        }
        $d = array();
        foreach ($this->promos as $did => $dsc) {
            $d[$did] = $dsc->packToCart();
        }
        return $d;
    }

    function getItemsPrice()
    {

        if (!$this->items) {
            return 0;
        }

        /**
         * @var $mShop \Verba\Mod\Shop
         * @var $item \Verba\Mod\Cart\Item
         */
        $mShop = \Verba\_mod('shop');
        $items_price = 0;
        foreach ($this->items as $hash => $item) {
            $items_price += $mShop->convertCur($item->getPrice() * $item->getQuantity(), $item->getCurrencyId(), $this->getCurrencyId());
        }
        return $items_price;
    }

    protected function getSomeRecountedValue($mtd, $args)
    {
        if (!method_exists($this, $mtd)) {
            return null;
        }
        if ($this->total === null) {
            $this->recountTotal();
        }

        return call_user_func_array(array($this, $mtd), $args);
    }

    function recountTotal()
    {
        $this->total = 0;
        if (!$this->items) {
            return $this->total;
        }
        /**
         * @var $mShop \Verba\Mod\Shop
         * @var $item \Verba\Mod\Cart\Item
         */
        $mShop = \Verba\_mod('shop');
        foreach ($this->items as $hash => $item)
        {
            $this->total += $mShop->convertCur($item->getFinalPrice() * $item->getQuantity(), $item->getCurrencyId(), $this->getCurrencyId());
        }
        return $this->total;
    }

    function _rc_getTotal()
    {
        return $this->total;
    }

    function recountDiscount()
    {
        $this->discount = 0;

        if (empty($this->promos)) {
            return $this->discount;
        }

        //Order-affected Discounts
        $promos = $this->getPromosByAffect('order');
        foreach ($promos as $pid => $Discount) {
            $Discount->recount();
            $this->discount += $Discount->value;
        }

        settype($this->discount, 'float');
        return $this->discount;
    }

    function _rc_getDiscount()
    {
        return $this->discount;
    }

    function recountShipping()
    {
        $this->shipping = 0;
        $this->shippingDelta = 0;

        $ctotal = -1;
        if ($this->shippingFree > 0) {
            $ctotal = $this->getTotal() - $this->getDiscount();
        }

        if ($ctotal < 0 || $ctotal <= $this->shippingFree) {
            $this->shipping = $this->shippingCost;
            $this->shippingDelta = $this->shippingFree - $ctotal;
        }
        return $this->shipping;
    }

    function _rc_getShipping()
    {
        return $this->shipping;
    }

    function recountTopay()
    {
        if ($this->getTotal() == 0) {
            $this->topay = 0;
        } else {
            $this->topay = $this->getTotal() - $this->getDiscount() + $this->getShipping();
        }
    }

    function _rc_getTopay()
    {
        return $this->topay;
    }
}