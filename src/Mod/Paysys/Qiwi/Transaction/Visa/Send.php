<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 14:25
 */

namespace Mod\Paysys\Qiwi\Transaction\Visa;


class Send extends \Verba\Mod\Paysys\Qiwi\Transaction
{
    public $clientAccount;
    public $requestData;
    public $url;
    public $payTrans = array();
    public $payRqId;
    public $createBillResponseCode;
    public $createBillResponse;

    function __construct($orderId, $proto)
    {
        parent::__construct($orderId, $proto);

        // user phone number
        if (isset($_REQUEST['qiwi_client_account'])
            && preg_match("/(\d{1,15})/i", $_REQUEST['qiwi_client_account'], $buff)
            && isset($buff[1])) {
            $this->clientAccount = $buff[1];
        }

        $this->url = $this->proto->cfg['paymentUrl'];
        $this->purchaseTime = date('ymdHis');
        $this->purchaseDesc = $this->genOrderDesc();
        $this->payRqId = $this->logRq();

        $this->createBill();

        if ($this->createBillResponseCode !== 0 && $this->createBillResponseCode !== 215) {
            if ($response_msg = \Verba\Lang::get('qiwi createBill ' . $this->createBillResponseCode)) {

            } elseif (isset($this->createBillResponse->response->description)) {
                $response_msg = $this->createBillResponse->response->description;
            }
            $this->log()->error('Unable to create payment bill. payRq:' . $this->payRqId
                . ' QIWI response code:' . $this->createBillResponseCode
                . ' msg: ' . $response_msg);
            throw new Exception('Unable to create payment bill. Error (' . $this->createBillResponseCode . ') ' . $response_msg);
        }

        $this->requestData = $this->genRequestData();
        $this->updateLog(array(
            'requestData' => var_export($this->requestData, true),
        ));
    }

    function createBill()
    {

        $url = $this->proto->cfg['createBillUrl'];
        $url = preg_replace(
            array('/\{prv_id\}/', '/\{bill_id\}/'),
            array($this->merchantId, $this->orderCode),
            $url);

        $post = array(
            'user' => 'tel:+' . $this->clientAccount,
            'amount' => $this->totalAmount,
            'ccy' => strtoupper($this->currency->p('intCode')),
            'comment' => $this->purchaseDesc,
            'lifetime' => date('Y-m-d\TH:i:s', mktime() + $this->paysys->payment_awaiting),
            'pay_source' => 'qw',
            'prv_name' => \Verba\Lang::getFromLang($this->orderData->lc, 'order shopName'),
        );

        $params = array(
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_POST => 1,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->proto->cfg['api_id'] . ':' . $this->proto->cfg['api_pass'],
            CURLOPT_POSTFIELDS => http_build_query($post),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
            ),
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, $params);
        $rawResponse = curl_exec($ch);
        curl_close($ch);
        if (!$rawResponse) {
            $error_msg = 'Unsuccess createBill request. curl error:' . curl_error($ch) . ', curl params:[' . var_export($params, true) . ']';
            $this->log->error($error_msg);
            throw new Exception($error_msg);
        }

        $this->createBillResponse = json_decode($rawResponse);
        $this->createBillResponseCode = isset($this->createBillResponse->response->result_code)
            ? (int)$this->createBillResponse->response->result_code
            : null;

        $this->updateLog(array(
            'createBillResponse' => var_export($this->createBillResponse, true),
            'createBillResponseCode' => $this->createBillResponseCode
        ));
        return true;
    }

    function genRequestData()
    {
        $successUrl = new \Url($this->proto->cfg['successUrl']);
        $failUrl = new \Url($this->proto->cfg['failureUrl']);

        $data = array(
            'shop' => $this->merchantId,
            'transaction' => $this->orderCode,
            'successUrl' => $successUrl->get(true),
            'failUrl' => $failUrl->get(true),
            'qiwi_phone' => $this->clientAccount,
        );

        $this->addExtsToArray($data);
        return $data;
    }

    function logRq()
    {
        $q = "INSERT INTO `" . SYS_DATABASE . "`.`" . $this->proto->getPayLogTable() . "` (
`purchaseTime`,
`orderId`,
`orderCode`,
`totalAmount`,
`currencyId`,
`description`,
`owner`,
`client_account`
) VALUES (
  '" . $this->purchaseTimeToSql($this->purchaseTime) . "',
  '" . $this->orderId . "',
  '" . $this->orderCode . "',
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

    function updateLog($data)
    {
        $f = array();
        foreach ($data as $k => $v) {
            $f[] = "`" . $k . "`='" . $this->DB()->escape_string($v) . "'";
        }

        $f = implode(', ', $f);

        $q = "UPDATE `" . SYS_DATABASE . "`.`" . $this->proto->getPayLogTable() . "` SET
" . $f . "
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