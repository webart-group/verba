<?php

namespace Mod;
/*
\Verba\_mod('customer');
\Verba\_mod('order');
\Verba\_mod('currency');
\Verba\_mod('paysys');
*/
//require_once(\Verba\_mod('shop')->getModDirRel() . '/products/product.ptype.php');
//require_once(__DIR__ . '/CartItemInstance.php');
//require_once(__DIR__ . '/CartItemInstanceResource.php');

class Cart extends \Verba\Mod
{
    use \Verba\ModInstance;
    /**
     * @var \Mod\Cart\CartInstance
     */
    protected $cart;

    function init()
    {
        if (!is_object($this->cart = $this->loadFromSession())) {
            $customerProfile = Customer::getInstance()->getProfile();
            $this->cart = new Cart\CartInstance($customerProfile);
        } else {
            $this->cart->refresh();
        }

        return $this->cart;
    }

    function __call($mthd, $args)
    {
        if (!is_object($this->cart)
            || !method_exists($this->cart, $mthd)) {
            return false;
        }

        return call_user_func_array(array($this->cart, $mthd), $args);
    }

    function __destruct()
    {
        $this->saveToSession('cartInstance', serialize($this->cart));
    }

    function loadFromSession()
    {

        $customerProfile = \Mod\Customer::getInstance()->getProfile();

        if (!$customerProfile) {
            return false;
        }
        $cartInstance = $this->getFromSession('cartInstance');
        if (//(bool)$S->gC('cacheEnable') &&

            is_string($cartInstance)
            && is_object($cartInstance = unserialize($cartInstance))
            && $cartInstance instanceof \Mod\Cart\CartInstance) {
            $Profile = $cartInstance->getProfile();
            if (!$Profile || !$Profile instanceof \Mod\Customer\Profile) {
                return false;
            }
            $cart_customer_timestamp = $Profile->getDbTimestamp();
            $customer_timestamp = $customerProfile->getDbTimestamp();

            if ($customerProfile->getId() != $cartInstance->getCustomerId()
                || $Profile->getId() != $cartInstance->getCustomerId()) {
                return false;
            }
            if ($customer_timestamp &&
                $cart_customer_timestamp != $customer_timestamp) {
                $cartInstance->refreshCustomerProfile($customerProfile);
            }

            $profile_required_update = $customerProfile->getUpdstamp();
            if ($profile_required_update == 1) {
                $customerProfile->updstampHandled();
            }
            $cartInstance->loadGlobalDsicounts();
            $cartInstance->refresh();
            return $cartInstance;
        }
        return false;
    }

    function switchCurrentCart($cart)
    {

        if (!$cart instanceof \Mod\Cart\CartInstance
            || $cart->getCustomerId() != \Verba\_mod('customer')->getProfile()->getId()) {
            return false;
        }
        $this->cart = $cart;
        $this->saveToSession('cartInstance', serialize($this->cart));
        return true;
    }

    /**
     * @return \Mod\Cart\CartInstance
     */
    function getCartInstance()
    {
        return $this->cart;
    }

    /**
     * @return \Mod\Cart\CartInstance
     */
    function getCart()
    {
        return $this->getCartInstance();
    }

    function switchCustomerByEmail($bp = null)
    {
        $email = isset($bp['email']) ? $bp['email'] : $_REQUEST['email'];
        $mCustomer = \Verba\_mod('customer');
        list($profile, $Cart) = $mCustomer->finalizeProfileAndCart($email, false);
        if (!$profile || !$Cart instanceof \Mod\Cart\CartInstance) {
            throw Exception();
        }
        $cfg = $Cart->packToCfg();
//    $gdiscounts = array();
//    if(isset($cfg['discounts']) && is_array($cfg['discounts'])){
//      foreach($cfg['discounts'] as $did => $discount){
//        if($discount['context'] != 'global'){
//          continue;
//        }
//        $gdiscounts[$did] = $discount;
//      }
//    }
        $r = array(
            'customer' => $cfg['customer'],
            'discounts' => $cfg['discounts'],
            'items' => $cfg['items'],
        );

        return $r;
    }

    function renewCartByProfile($profile)
    {
        $cCart = $this->cart;
        if ($profile === $cCart->getProfile()) {
            return $cCart;
        }
        $curr = $cCart->getCurrency();
        $nCart = new \Mod\Cart\CartInstance($profile, false);
        $nCart->currencyChange($curr->getId());
        $nCart->setPaysys($cCart->getPaysysId());
        $nCart->clearItems();
        $items = $cCart->getItems();
        foreach ($items as $k => $Item) {
            $nCart->addItem($Item->exportToArray());
        }
        $nCart->refresh();
        return $nCart;
    }

}
