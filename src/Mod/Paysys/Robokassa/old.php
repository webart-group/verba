<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 15:48
 */

class PayTransaction_Robokassa extends Base
{

    protected $isValid;
    public $payRqId;
    protected $_paysysCode = 'robokassa';
    protected $_modCode = 'robokassa';
    public $payTrans = array();
    public $orderId;
    public $orderCode;
    public $purchaseTime;
    public $purchaseDesc;
    public $sessionId;
    public $signature;
    public $orderData;
    public $mCfg;
    public $merchantId;
    public $status;
    public $code;
    public $outSum;
    public $extParams = array(
        'payRqId', 'orderCode'
    );
    /**
     * @var \Verba\Model\Currency
     */
    public $currency;
    public $sysCur;

    function __construct($orderSid)
    {
        $this->log();
        sort($this->extParams);
        $this->loadOrderData($orderSid);
        if (is_object($this->orderData)) {
            $this->orderId = $this->orderData->id;
            $this->orderCode = $this->orderData->code;
        }
        $this->paysys = \Verba\_mod('payment')->getPaysys($this->_paysysCode);
        $this->mCfg = \Verba\_mod('payment')->getPaysysMod($this->_modCode)->gC();

        $this->currency = \Verba\_mod('currency')->getCurrency($this->orderData->currencyId);
        $this->merchantId = $this->mCfg['merchantId'];

        $this->sysCur = $this->paysys->getCurrency($this->mCfg['sysCurrencyId']);
        $this->sessionId = session_id();
    }

    function setOrderData($orderData)
    {
        if (!$orderData instanceof \Verba\Mod\Order\Model\Order) {
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
            $this->log->error('Order not found');
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

    function setPayRqId($val)
    {
        $val = (int)$val;
        if (!$val || $this->payRqId !== null) {
            return false;
        }
        $this->payRqId = $val;
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
        return \Verba\Lang::get('robokassa codes ' . $tranCode);
    }

    function loadPayTrans()
    {
        $r = array();
        if (!$this->orderId) {
            return $r;
        }
        $q = "SELECT * FROM " . SYS_DATABASE . ".`" . $this->mCfg['payLogTable'] . "` WHERE "
            . "`orderId` = '" . $this->orderId . "' && `id` = '" . $this->DB()->escape_string($this->payRqId) . "'";
        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getNumRows()) {
            return $r;
        }

        return $sqlr->fetchRow();
    }

    function addExtsToArray(&$r)
    {
        \Verba\reductionToArray($r);
        if (!count($this->extParams)) {
            return;
        }

        foreach ($this->extParams as $pName) {
            $r['shp_' . $pName] = $this->$pName;
        }
    }

    function genSignatureHashByData($baseData)
    {
        \Verba\reductionToArray($baseData);
        if (is_array($this->extParams) && count($this->extParams)) {
            foreach ($this->extParams as $pName) {
                $baseData[] = 'shp_' . $pName . '=' . $this->$pName;
            }
        }
        $r = implode(':', $baseData);
        return md5($r);
    }
}

class PaySend_Robokassa extends PayTransaction_Robokassa
{
    public $requestData;
    public $url;

    function __construct($orderSid)
    {
        parent::__construct($orderSid);

        $this->url = $this->mCfg['paymentUrl'];
        $this->outSum = \Verba\reductionToCurrency($this->orderData->getBaseToPay() * $this->sysCur->p('rate'));
        $this->totalAmount = $this->orderData->getTopay();
        $this->purchaseTime = date('ymdHis');
        $this->purchaseDesc = htmlspecialchars(Lang::get('order invoiceText', array('invCode' => $this->orderCode)));

        $this->payRqId = $this->logRq();

        $this->signature = $this->genSignature();
        $this->requestData = $this->genRequestData();
        $this->updateLog();
    }

    function genRequestData()
    {

        $data = array(
            'MrchLogin' => $this->merchantId,
            'OutSum' => $this->outSum,
            'InvId' => $this->orderId,
            'Desc' => $this->purchaseDesc,
            'SignatureValue' => $this->signature,
            'IncCurrLabel' => $this->currency->p('intCode'),
            'Email' => $this->orderData->email,
            'Culture' => 'ru',
        );
        $this->addExtsToArray($data);
        return $data;
    }

    function genSignature()
    {
        $r = array(
            $this->mCfg['merchantId'],
            $this->outSum,
            $this->orderId,
            $this->mCfg['pass1']
        );
        $r = $this->genSignatureHashByData($r);
        return $r;
    }

    function logRq()
    {
        $q = "INSERT INTO `" . SYS_DATABASE . "`.`" . $this->mCfg['payLogTable'] . "` (
`purchaseTime`,
`orderId`,
`outSum`,
`totalAmount`,
`currencyId`,
`description`,
`owner`,
`merchantId`
) VALUES (
  '" . $this->purchaseTimeToSql($this->purchaseTime) . "',
  '" . $this->orderId . "',
  '" . $this->outSum . "',
  '" . $this->totalAmount . "',
  '" . $this->currency->getId() . "',
  '" . $this->DB()->escape_string($this->purchaseDesc) . "',
  '" .\Verba\User()->getID() . "',
  '" . $this->merchantId . "'
)";
        $sqlr = $this->DB()->query($q);
        if (!$sqlr) {
            $error = $this->DB()->getLastError();
            $this->log()->error('Unable to create PaySent request log entry. SQL-error:' . var_export($error, true));
            return false;
        }
        return $sqlr->getInsertId();
    }

    function updateLog()
    {
        $q = "UPDATE `" . SYS_DATABASE . "`.`" . $this->mCfg['payLogTable'] . "` SET
`signature` = '" . $this->signature . "',
`requestData` = '" . $this->DB()->escape_string(var_export($this->requestData, true)) . "'
WHERE
`id` = '" . $this->payRqId . "'
&& `orderId` = '" . $this->orderId . "'";

        $sqlr = $this->DB()->query($q);
        if (!$sqlr) {
            $error = $this->DB()->getLastError();
            $this->log()->error('Unable to update PaySent request log entry. SQL-error:' . var_export($error, true));
            return false;
        }
        return true;
    }

}

class PayNotify_Robokassa extends PayTransaction_Robokassa
{

    public $responseData = '';
    protected $isValid;
    public $status;
    public $statusMsg;
    public $notifyId;

    function __construct($orderSid, $ct = false)
    {
        parent::__construct($orderSid);

        if (!$ct) {
            $ct = &$_REQUEST;
        }
        $this->responseData = $ct;
        $this->signature = $ct['SignatureValue'];
        $this->outSum = $ct['OutSum'];
        $this->extractSHP($ct);
        if (!$this->signature
            || !$this->orderId
            || !$this->outSum) {
            throw new Exception('Notify response data does not contain required data. Request' . "\n" . var_export($ct, true));
        }

        $this->payTrans = $this->loadPayTrans();

        $this->validate();
        $this->status = $this->genStatus();
        $this->notifyId = $this->logRq();

    }

    function genStatus()
    {
        if (!$this->isValid) {
            return 'not_valid';
        }
        if (isset($this->payTrans['status'])
            && $this->payTrans['status'] == 'success') {
            $this->statusMsg = 'Secondary Notify for existing success pay status';
            return 'not_valid';
        }

        return 'success';
    }

    function validate()
    {
        if ($this->isValid === false) {
            return false;
        }
        $i = 0;
        if (!$this->validateSignature()) {
            --$i;
        }

        if (!is_array($this->payTrans)
            || !count($this->payTrans)
            || !isset($this->payTrans['id'])
            || $this->payTrans['id'] != $this->payRqId) {
            $this->statusMsg = 'Pay Transaction not found';
            $this->log->error('$this->payRqId:' . var_export($this->payRqId, true) . ', $this->payTrans:' . var_export($this->payTrans, true));
            --$i;
        }

        $reqSum = \Verba\reductionToCurrency($this->orderData->getTopay());
        $notifySum = \Verba\reductionToCurrency($this->outSum);
        $payRqSum = \Verba\reductionToCurrency($this->payTrans['outSum']);
        if ($reqSum != $notifySum
            || $payRqSum != $reqSum) {
            $this->statusMsg = 'Notify summ is mismatch';
            $this->log->error('reqSum: ' . $reqSum . ', notifySum:' . $notifySum . ', payRqSum: ' . $payRqSum);
            --$i;
        }

        $this->isValid = !($i < 0);

        return $this->isValid;
    }

    function extractSHP($ct)
    {
        if (!is_array($ct)) {
            return false;
        }
        foreach ($this->extParams as $i => $pName) {
            $key = 'shp_' . $pName;
            if (!array_key_exists($key, $ct)) {
                continue;
            }
            $mtd = 'set' . ucfirst($pName);
            if (is_callable(array($this, $mtd))) {
                $this->$mtd($ct[$key]);
            } elseif (property_exists($this, $pName)) {
                $this->$pName = $ct[$key];
            }
        }

    }

    function validateSignature()
    {
        $r = array(
            $this->outSum,
            $this->orderId,
            $this->mCfg['pass2']
        );
        $r = $this->genSignatureHashByData($r);

        if (strtolower($r) == strtolower($this->signature)) {
            return true;
        }
        $this->statusMsg = 'Signature verification error';
        $this->log->error('generated sig:' . var_export($r, true) . ', fromRequest:' . var_export($this->signature, true));
        return false;
    }

    function isValid()
    {
        return $this->isValid;
    }

    function logRq()
    {
        $this->DB();
        $q = "INSERT INTO `" . SYS_DATABASE . "`.`" . $this->mCfg['notifyLogTable'] . "` (
`created`,
`orderId`,
`rqId`,
`ip`,
`responseData`,
`validated`,
`signature`,
`status`,
`statusMsg`,
`log`
) VALUES (
  '" . strftime("%Y-%m-%d %H:%M:%S") . "',
  '" . $this->orderId . "',
  '" . $this->DB->escape_string($this->payRqId) . "',
  '" . ip2long(\Verba\getClientIP()) . "',
  '" . $this->DB->escape_string(var_export($this->responseData, true)) . "',
  '" . $this->isValid . "',
  '" . $this->signature . "',
  '" . $this->DB->escape_string($this->status) . "',
  '" . $this->DB->escape_string($this->statusMsg) . "',
  '" . $this->DB->escape_string($this->log->getMessagesAsStr()) . "'
)";
        $sqlr = $this->DB->query($q);
        if (!$sqlr) {
            $error = $this->DB()->getLastError();
            $this->log()->error('Unable to create Notify log entry. SQL-error:' . var_export($error, true));
            return false;
        }

        return $sqlr->getInsertId();
    }

    function updateTransactionByNotify()
    {
        $this->DB();
        if (!$this->payRqId) {
            $this->log()->error('Unable to update Pay Request log entry - payRqId is empty. ' . var_export($this, true));
            return false;
        }

        $q = "UPDATE `" . SYS_DATABASE . "`.`" . $this->mCfg['payLogTable'] . "` SET
`updated` = '" . strftime("%Y-%m-%d %H:%M:%S") . "',
`status` = '" . $this->DB->escape_string($this->status) . "',
`notifyId` = '" . $this->notifyId . "'
WHERE
`id` = '" . $this->DB->escape_string($this->payRqId) . "'
&& `orderId` = '" . $this->orderId . "'";

        $sqlr = $this->DB->query($q);
        if (!$sqlr || !$sqlr->getAffectedRows()) {
            $this->log()->error('Unable to update Pay Request log entry payRqId. ' . var_export($this, true));
            return false;
        }
        return $sqlr->getInsertId();
    }

    function getStatusMsg()
    {
        return $this->statusMsg;
    }

}

class OrderTransRobokassa extends \Verba\Mod\Order\Transaction
{

}

