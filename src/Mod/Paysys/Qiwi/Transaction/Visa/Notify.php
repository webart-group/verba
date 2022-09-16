<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 14:25
 */

namespace Verba\Mod\Paysys\Qiwi\Transaction\Visa;


class Notify extends \Verba\Mod\Paysys\Qiwi\Transaction
{

    public $responseData = '';
    protected $isValid;
    public $status;
    public $statusMsg;
    public $notifyId;

    function __construct($orderId, $proto, $ct = false)
    {
        parent::__construct($orderId, $proto);

        if (!$ct) {
            $ct = &$_POST;
        }

        $this->responseData = $ct;

        $this->payTrans = $this->loadPayTrans();
        $this->payRqId = $this->extractPayRqId($this->payTrans);
        $this->validate();
        $this->status = $this->genStatus();
        $this->notifyId = $this->logRq();
    }

    function genStatus()
    {
        if (!$this->isValid) {
            return 'not_valid';
        }

        switch ($this->responseData['status']) {
            case 'paid':
                if ($this->orderData->status == 21) {
                    $this->statusMsg = 'Secondary Notify for existing success pay status';
                    return 'repetition';
                }
                $st = 'success';
                break;

            case 'waiting':
                $st = 'wait';
                break;

            case 'rejected':
            case 'unpaid':
            case 'expired':
                $st = 'error';
                $this->statusMsg = $this->getMsgByTranCode($this->responseData['error']);
                break;
            default:
                $st = 'not_valid';
        }

        return $st;
    }

    function validate()
    {
        if ($this->isValid === false) {
            return false;
        }
        $i = 0;

        if ($this->responseData['bill_id'] != $this->orderCode) {
            --$i;
            $this->statusMsg = 'OrderId is incorrect';
        }
        $ip = \Verba\getClientIP();
        if (!$this->validateIp($ip)) {
            --$i;
            $this->statusMsg = 'Bad IP';
            $this->log->error('Pay notify bad IP:' . $ip);
        }


        $notifySum = \Verba\reductionToCurrency($this->responseData['amount']);
        if ($this->totalAmount != $notifySum) {
            $this->statusMsg = 'Notify summ is mismatch';
            $this->log->error('orderSum: ' . $this->totalAmount . ', notifySum:' . $notifySum);
            --$i;
        }
        $rqSignature = null;
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $rqSignature = $headers['X-Api-Signature'];
        } elseif (is_array($_SERVER) && array_key_exists('HTTP_X_API_SIGNATURE', $_SERVER)) {
            $rqSignature = $_SERVER['HTTP_X_API_SIGNATURE'];
        }

        $sigData = array(
            \Verba\reductionToCurrency($this->totalAmount),
            $this->orderCode,
            strtoupper($this->currency->p('intCode')),
            $this->responseData['command'],
            $this->responseData['comment'],
            $this->responseData['error'],
            $this->responseData['prv_name'],
            $this->responseData['status'],
            $this->responseData['user'],
        );
        $strTohash = implode('|', $sigData);
        $signature = base64_encode(hash_hmac('sha1', $strTohash, $this->proto->cfg['notifypass'], true));

        if (empty($rqSignature) || $signature != $rqSignature) {
            $this->statusMsg = 'Signature verification error';
            $this->log->error('generated sig:' . var_export($signature, true) . ', fromRequest:' . var_export($rqSignature, true) . ' strTohash: ' . var_export($strTohash, true));
            --$i;
        }

        $this->isValid = !($i < 0);

        return $this->isValid;
    }

    function validateIp($ip)
    {
        if (!is_array($this->mCfg['trustedIP']) || !count($this->mCfg['trustedIP'])) {
            return true;
        }
        $ip = '91.232.231.34';
        if (!class_exists('Network_Simple', false)) {
            require_once(SYS_EXTERNALS_DIR . '/class.Network_Simple.php');
        }
        $nw = new \Network_Simple();

        foreach ($this->mCfg['trustedIP'] as $network) {
            if ($nw->isInSubnet($network, $ip)) {
                return true;
            }
        }
        return false;
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

    function updateTransactionByNotify()
    {
        $this->DB();
        if (!$this->payRqId) {
            $this->log()->error('Unable to update Pay Request log entry - payRqId is empty.');
            return false;
        }

        $q = "UPDATE `" . SYS_DATABASE . "`.`" . $this->proto->cfg['payLogTable'] . "` SET
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

}