<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 14:40
 */


/*
class QiwiProtocolSoap extends \PaymentProtocol
{

    function createPaysend($orderId)
    {
        return new PaySend_QiwiSoap($orderId, $this);
    }

    function getNotify($orderId, $soapParams)
    {
        return new PayNotify_QiwiSoap($orderId, $soapParams, $this);
    }

}

class QiwiSoapResponse
{
    public $updateBillResult;
}

class QiwiSoapParam
{
    public $login;
    public $password;
    public $txn;
    public $status;
}

class PaySend_QiwiSoap extends PayTransaction_Qiwi
{
    public $requestData;
    public $url;
    public $payRqId;

    function __construct($orderId, $proto)
    {
        parent::__construct($orderId, $proto);
        if (isset($_REQUEST['qiwi_client_account'])
            && preg_match("/(\d{10})/i", $_REQUEST['qiwi_client_account'], $buff)
            && isset($buff[1])) {
            $this->clientAccount = $buff[1];
        }

        $this->url = $this->proto->cfg['paymentUrl'];
        $this->totalAmount = $this->orderData->getTopay();
        $this->purchaseTime = date('ymdHis');
        $this->purchaseDesc = htmlspecialchars($this->orderData->description);
        $this->payRqId = $this->logRq();

        $this->requestData = $this->genRequestData();
        $this->updateLog();
    }

    function genRequestData()
    {

        $data = array(
            'from' => $this->merchantId,
            'to' => $this->clientAccount,
            'summ' => $this->totalAmount,
            'com' => $this->purchaseDesc,
            'currency' => $this->currency->p('intCode'),
            'lifetime' => $this->paysys->payment_awaiting / 3600,
            'txn_id' => $this->orderCode
        );

        $this->addExtsToArray($data);
        return $data;
    }

    function logRq()
    {
        $q = "INSERT INTO `" . SYS_DATABASE . "`.`" . $this->proto->getPayLogTable() . "` (
`purchaseTime`,
`orderId`,
`totalAmount`,
`currencyId`,
`description`,
`owner`,
`client_account`
) VALUES (
  '" . $this->purchaseTimeToSql($this->purchaseTime) . "',
  '" . $this->orderId . "',
  '" . $this->totalAmount . "',
  '" . $this->currency->getId() . "',
  '" . $this->DB()->escape_string($this->purchaseDesc) . "',
  '" .\Verba\User()->getID() . "',
  '" . $this->clientAccount . "'
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
        $q = "UPDATE `" . SYS_DATABASE . "`.`" . $this->proto->getPayLogTable() . "` SET
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

class PayNotify_QiwiSoap extends PayTransaction_Qiwi
{

    public $responseData = '';
    protected $isValid;
    public $status;
    public $statusMsg;
    public $notifyId;

    function __construct($orderId, $soapParams, $proto)
    {
        parent::__construct($orderId, $proto);

        if (!$this->orderId) {
            throw new Exception('Notify response data does not contain required data. Request' . "\n" . var_export($ct, true));
        }
        if (!is_object($soapParams)
            || !isset($soapParams->txn)
            || !isset($soapParams->login)
            || !isset($soapParams->password)
            || !isset($soapParams->status)
        ) {
            $this->log->error('$soapParams:' . var_export($soapParams, true));
            throw new Exception('SoapParams Missing');
        }
        $this->responseData = $soapParams;

        $this->validate();
        $this->status = $this->genStatus();
        $this->notifyId = $this->logRq();
    }

    function genStatus()
    {
        if (!$this->isValid) {
            return 'not_valid';
        }

        if ($this->responseData->status == 60) {
            if ($this->orderData->status == 21) {
                $this->statusMsg = 'Secondary Notify for existing success pay status';
                return 'repetition';
            }
            return 'success';
        } elseif ($this->responseData->status > 100) {
            return 'error';
        } elseif ($this->responseData->status >= 50 && $this->responseData->status < 60) {
            return 'wait';
        }

        return 'not_valid';

    }

    function validate()
    {
        if ($this->isValid === false) {
            return false;
        }
        $i = 0;

        if ($this->responseData->login != $this->merchantId) {
            --$i;
            $this->statusMsg = 'Login is incorrect';
        }

        $signature = strtoupper(md5($this->orderCode . strtoupper(md5($this->proto->cfg['pass']))));
        if (strlen($this->responseData->password) != 32
            || $signature != $this->responseData->password) {
            $this->statusMsg = 'Signature verification error';
            $this->log->error('generated sig:' . var_export($signature, true) . ', fromRequest:' . var_export($this->responseData->password, true));
            --$i;
        }

        if (!is_array($this->payTrans)
            || !count($this->payTrans)) {
            $this->statusMsg = 'Pay Request not found';
            $this->log->error($this->statusMsg);
            --$i;
        }

        $orderSum = \Verba\reductionToCurrency($this->orderData->getTopay());
        $notifySum = \Verba\reductionToCurrency($this->totalAmount);

        if ($orderSum != $notifySum) {
            $this->statusMsg = 'Notify summ is mismatch';
            $this->log->error('OrderSum: ' . $orderSum . ', notifySum:' . $notifySum);
            --$i;
        }

        $this->isValid = !($i < 0);

        return $this->isValid;
    }

    function isValid()
    {
        return $this->isValid;
    }

    function logRq()
    {
        $this->DB();

        $f = array(
            'created' => strftime("%Y-%m-%d %H:%M:%S"),
            'orderId' => $this->orderId,
            'rqId' => $this->payRqId,
            'ip' => ip2long(\Verba\getClientIP()),
            'responseData' => var_export($this->responseData, true),
            'validated' => $this->isValid,
            'status' => $this->status,
            'statusMsg' => $this->statusMsg,
            'log' => $this->log->getMessagesAsStr(),
        );

        $fieldsNames = $fieldsValues = '';
        foreach ($f as $fName => $fValue) {
            $fieldsNames .= '`' . $fName . '`,';
            $fieldsValues .= "'" . $this->DB->escape_string($fValue) . "',";
        }

        $q = "INSERT INTO `" . SYS_DATABASE . "`.`" . $this->proto->getNotifyLogTable() . "` (
    " . substr($fieldsNames, 0, -1) . "
    ) VALUES (
    " . mb_substr($fieldsValues, 0, -1) . "
    )";

        $sqlr = $this->DB->query($q);
        if (!$sqlr) {
            $error = $this->DB()->getLastError();
            $this->log()->error('Unable to create Notify log entry. SQL-error:' . var_export($error, true));
            return false;
        }

        return $sqlr->getInsertId();
    }

    function getStatusMsg()
    {
        return $this->statusMsg;
    }

}
*/