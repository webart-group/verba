<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 16:18
 */

namespace Verba\Mod\Order;


class CreateData extends \Verba\Configurable{
    /**
     * @var array Контейнер для данных формы заказа
     */
    public $data = array();
    /**
     * @var array массив с доп. данными для создания заказа
     */
    public $extended = array();
    public $items = array();

    public $customerId;
    /**
     * @var \Verba\Mod\Cart\CartInstance
     */
    public $Cart;

    /**
     * @var \Model\Store
     */
    public $Store;

    // feats
    public $clearCart = true;
    public $sendEmails = true;
    public $updateCustomerProfile = true;

    private $__formated = false;

    /**
     * Constructor.
     * @param array $cfg
     */
    function __construct($cfg = array()){
        $this->initConfigurator();
        $this->applyConfigDirect($cfg);
    }

    function format(){
        if($this->__formated){
            return true;
        }
        $this->__formated = true;

        $_order = \Verba\_oh('order');
        $mCart = \Verba\_mod('cart');
        $mCustomer = \Verba\_mod('Customer');

        if(!is_array($this->data) || !count($this->data)){
            if(isset($_REQUEST['NewObject'][$_order->getID()]) && is_array($_REQUEST['NewObject'][$_order->getID()])){
                $this->data = $_REQUEST['NewObject'][$_order->getID()];
            }
        }

        if(!is_array($this->extended))
        {
            $this->extended = [];
        }

        list($customerProfile, $this->Cart) = $mCustomer->finalizeProfileAndCart($this->data['email'], true);

        if(!is_object($this->Cart) || !$this->Cart instanceof \Verba\Mod\Cart\CartInstance){
            return false;
        }

        if(!is_array($this->items) || !count($this->items)){
            $this->items = $this->Cart->getItems();
        }

        // Магазин
        $mStore = \Verba\_mod('store');
        //Достаем id магазина из первого товара Корзины
        foreach($this->Cart->getItems() as $hash => $Item){
            $this->Store = $mStore->OTIC()->getItem($Item->storeId);
            continue;
        }

        $this->customerId = $customerProfile->getId();

        if(!isset($this->data['currencyId'])){
            $currency = $mCart->getCurrency();
            $this->data['currencyId'] = $currency->getId();
        }

        $this->Cart->setPaysys($this->data['paysysId']);
        $this->Cart->refresh();
        return true;
    }

    function validate(){
        if(!$this->__formated){
            $this->format();
        }
        $i = 0;

        if(!is_string($this->customerId) || !strlen($this->customerId)){
            $this->log()->error('CreateOrder `customer` is invalid');
            $i--;
        }

        if(!is_object($this->Cart) || !$this->Cart instanceof \Verba\Mod\Cart\CartInstance){
            $this->log()->error('CreateOrder `Cart` is invalid');
            $i--;
        }
        if(!is_array($this->items) || empty($this->items)){
            $this->log()->error('CreateOrder `items` is invalid');
            $i--;
        }

        if(!is_object($this->Store)){
            throw  new \Verba\Exception\Building('CreateOrder Bad Store');
        }

        if(!is_array($this->data)){
            $this->log()->error('CreateOrder `data` is invalid');
            $i--;
        }
        if(!isset($this->data['currencyId'])){
            $this->log()->error('CreateOrder `Currency` is invalid');
            $i--;
        }
        if(!isset($this->data['paysysId'])
            || !is_object($Ps = \Verba\_mod('payment')->getPaysys($this->data['paysysId']))
            || !$Ps->active){
            $this->log()->error('CreateOrder `Paysys` is invalid');
            $i--;
        }

        if(!isset($this->data['email']) || empty($this->data['email'])){
            $this->log()->error('CreateOrder `Email` is invalid');
            $i--;
        }

        return $i === 0;
    }
}