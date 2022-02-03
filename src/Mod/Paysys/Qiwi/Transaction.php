<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 14:23
 */

namespace Mod\Paysys\Qiwi;


class Transaction extends \Verba\Base
{

    protected $_paysysCode = 'qiwi';
    protected $_modCode = 'qiwi';
    protected $mod;
    public $proto;
    public $orderId;
    public $orderCode;
    public $totalAmount;
    public $purchaseTime;
    public $purchaseDesc;
    public $sessionId;
    public $signature;
    public $orderData;
    public $mCfg;
    public $merchantId;
    public $status;
    public $code;
    public $clientAccount;

    public $extParams = array();
    /**
     * @var \Verba\Model\Currency
     */
    public $currency;

    function __construct($orderId, $proto)
    {
        $this->proto = $proto;
        $this->log();
        $this->loadOrderData($orderId);
        if (is_object($this->orderData)) {
            $this->orderId = $this->orderData->id;
            $this->orderCode = $this->orderData->code;
        } else {
            throw new \Exception('Notify response data does not contain required data. OrderId' . "\n" . var_export($orderId, true));
        }

        $this->paysys = \Verba\_mod('payment')->getPaysys($this->_paysysCode);
        $this->mod = \Verba\_mod('payment')->getPaysysMod($this->_modCode);
        $this->mCfg = $this->mod->gC();

        $this->currency = \Verba\_mod('currency')->getCurrency($this->orderData->currencyId);
        $this->merchantId = $this->mCfg['merchantId'];
        $this->totalAmount = \Verba\reductionToCurrency($this->orderData->getTopay() * $this->currency->p('rate'));

        $this->sessionId = session_id();
    }

    function setOrderData($orderData)
    {
        if (!$orderData instanceof \Mod\Order\Model\Order) {
            return false;
        }
        $this->orderData = $orderData;
    }

    function getOrderData()
    {
        if ($this->orderData === null) {
            $this->loadOrderData();
        }
        return $this->orderData;
    }

    function loadOrderData($orderUid)
    {
        $order = \Verba\_mod('order')->getOrder($orderUid);
        if (!$order) {
            $this->orderData = false;
            $this->log->error('Order not found. Request:' . var_export($_REQUEST, true));
            $this->isValid = false;
            return false;
        }
        $this->orderData = $order;
        return true;
    }

    function setOrderCode($val)
    {
        $val = (string)$val;
        if (!$val || $this->orderCode !== null) {
            return false;
        }
        $this->orderCode = $val;
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
        return \Verba\Lang::get('qiwi codes ' . $tranCode);
    }

    function addExtsToArray(&$r)
    {
        \Verba\reductionToArray($r);
        if (!count($this->extParams)) {
            return;
        }

        foreach ($this->extParams as $pName) {
            $r[$pName] = $this->$pName;
        }
    }

    function genOrderDesc()
    {
        return htmlspecialchars(\Lang::get('order invoiceText', array('invCode' => $this->orderCode)));
    }

    function loadPayTrans()
    {
        $r = array();
        if (!$this->orderId) {
            return $r;
        }
        $q = "SELECT * FROM " . SYS_DATABASE . ".`" . $this->proto->cfg['payLogTable'] . "` WHERE "
            . "`orderId` = '" . $this->orderId . "'
    ORDER BY `purchaseTime` DESC
    LIMIT 1";

        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getNumRows()) {
            return $r;
        }

        return $sqlr->fetchRow();
    }

    function extractPayRqId($arr)
    {
        if (!is_array($arr) || !isset($arr['id'])) {
            return false;
        }
        return (int)$arr['id'];
    }
}