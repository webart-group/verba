<?php

namespace Verba\Mod\Payment;

class Transaction extends \Verba\Base
{
    /**
     * @var \Model\Item
     */
    protected $_Tx;
    protected $_tx_code;
    /**
     * @var \Model
     */
    protected $_tx;
    public $io;
    protected $_paysysCode = '';

    /**
     * @var \Verba\Mod\Order\Model\Order
     */
    public $Order;

    public $orderCode;

    public $mCfg;

    /**
     * @description в Receive - метод операции для Notify операций; в Send - POST или GET
     *
     */
    public $method;
    public $url;
    public $paymentId;

    public $signature;
    public $status;
    public $description;
    public $request;
    public $response;
    public $sum;
    public $currencyId;
    protected $isValid;
    /**
     * @var \Verba\Model\Currency
     */
    public $currency;
    public $payTrans = array();

    protected $mod;
    /**
     * @var float сумма для оплаты на Платежном шлюзе
     */
    protected $paymentSum;

    function __construct($orderId)
    {

        if (is_object($orderId)) {
            $orderId = $orderId->iid;
        }

        $this->log();

        if (!is_object($this->Order)) {
            if (!is_object($this->Order = \Verba\Mod\Order::i()->getOrder($orderId))
                || !$this->Order->getId()) {
                $this->log->error('Order not found [' . var_export($orderId, true) . ']');
                throw new \Exception('Bad Order');
            }
        }

        if (!is_object($this->mod)) {
            $this->mod = \Verba\_mod('payment')->getModByCode($this->_paysysCode);
        }

        $this->paysys = \Verba\_mod('payment')->getPaysys($this->_paysysCode);
        $this->mCfg = $this->mod->gC();

        $this->paymentSum = $this->Order->gatewayPaymentSum();

        if (!is_string($this->_tx_code) && is_string($this->_tx_code = $this->mod->getTxCode())) {
            $this->_tx = \Verba\_oh($this->_tx_code);
        }

        $this->currency = $this->Order->getCurrency();

        $this->payTrans = $this->Order->getTrans();
    }

    function getPaymentSum()
    {
        return $this->paymentSum;
    }

    function genStatus()
    {

        if (!$this->isValid) {
            return 'error';
        }

        return 'success';
    }

    function getPaysysMod()
    {
        return $this->mod;
    }

    function getCurIntCode()
    {
        return $this->getPaysysMod()->convertOurCurCodeToIntCurCode($this->currency->p('code'));
    }

    function setOrder($orderData)
    {
        if (!$orderData instanceof \Verba\Mod\Order\Model\Order) {
            return false;
        }
        $this->Order = $orderData;
    }

    function getOrder()
    {
        return $this->Order;
    }

    function isOrderValid()
    {
        return is_object($this->Order) && $this->Order->getId() && $this->Order->active;
    }

    function purchaseTimeToSql($val)
    {
        if (!preg_match("/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/i", (string)$val, $_)) {
            return 0;
        }

        $str = '20' . $_[1] . '-' . $_[2] . '-' . $_[3] . ' ' . $_[4] . ':' . $_[5] . ':' . $_[6];
        return $str;
    }

    function getMsgByTranCode($tranCode)
    {
        return \Verba\Lang::get('unitpay codes ' . $tranCode);
    }

    function isValid()
    {
        if ($this->isValid === null) {
            $this->validate();
        }
        return $this->isValid;
    }

    function validate()
    {

        $this->isValid = false;

        if (!is_object($this->Order) || !$this->Order->getId() || !$this->Order->active) {
            return $this->isValid;
        }
        $this->isValid = true;

        return $this->isValid;
    }

    function getDescription()
    {
        return $this->description;
    }

    function getTx()
    {
        return $this->_Tx;
    }

    function createTx($data)
    {

        $_tx = \Verba\_oh($this->_tx_code);
        $ae = $_tx->initAddEdit('new');

        $data = $this->addBaseRqData($data);

        $ae->setGettedData($data);

        $ae->addedit_object();
        if (!$ae->getIID()) {
            return false;
        }
        $this->_Tx = $ae->getActualItem();

        return $this->_Tx->getId();
    }

    function addBaseRqData($ext)
    {
        if (!is_array($ext)) {
            $ext = array();
        }
        $arr = array();
        $arr['io'] = $this->io;
        $arr['status'] = $this->isValid ? 'success' : 'error';

        if (isset($this->url)) {
            $arr['url'] = $this->url;
        }

        if (isset($this->method)) {
            $arr['method'] = $this->method;
        }

        if (isset($this->paymentId)) {
            $arr['paymentId'] = $this->paymentId;
        }

        if (is_object($this->Order)) {
            $arr['orderId'] = $this->Order->getId();
            $arr['sum'] = $this->Order->getTopay();
            $arr['currencyId'] = $this->Order->getCurrency()->getId();
        }

        return array_replace_recursive($arr, $ext);
    }

    function logState()
    {
        $this->log()->event(get_class($this) . ': '
            . "\n" . 'isValid:' . var_export($this->isValid, true)
            . "\n" . 'description: ' . var_export($this->description, true)
            . "\n" . 'paymentId: ' . var_export($this->paymentId, true)
            . "\n" . 'url: ' . var_export($this->url, true)
        );
    }

    function l($str = null, $var2export = null)
    {
        if (!is_string($str)) {
            return $this->logState();
        }

        $this->log()->event(get_class($this) . ': '
            . $str . ($var2export !== null ? var_export($var2export, true) : ''));
        return true;
    }

}
