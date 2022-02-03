<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 15:20
 */

namespace Mod\Paysys\Liqpay\Transaction;


class Notify extends \Verba\Mod\Paysys\Liqpay\Transaction{

    public $transData = array();
    public $responseData = '';
    protected $isValid;
    public $code;
    public $pay_way;
    public $sender_phone;

    function __construct($orderSid, $ct = false){
        parent::__construct($orderSid);
        if(!$ct){
            $ct = &$_REQUEST;
        }
        $this->signature =  $ct['signature'];
        $this->responseData = base64_decode($ct['operation_xml']);
        $xml = simplexml_load_string($this->responseData);
        $json = json_encode($xml);
        $ct = json_decode($json,true);
        if(!is_array($ct) || !array_key_exists('merchant_id', $ct) || !array_key_exists('transaction_id', $ct)){
            throw new Exception('Notify response data does not contain obligatory fields');
        }

        $this->merchantId = isset($ct['merchant_id']) ?  $ct['merchant_id'] : false;
        $this->tranId = isset($ct['transaction_id']) ?  $ct['transaction_id'] : false;
        $this->version = isset($ct['version']) ?  $ct['version'] : false;
        $this->currencyCode = isset($ct['currency']) ?  $ct['currency'] : false;
        $this->totalAmount = isset($ct['amount']) ?  \Verba\reductionToCurrency($ct['amount']) : false;
        $this->purchaseDesc = isset($ct['description']) ? $ct['description'] : false;
        $this->status =  isset($ct['status']) ? $ct['status'] : false;
        $this->code = isset($ct['code']) && !empty($ct['code']) ? $ct['code'] : '';
        if(isset($ct['pay_way'])){
            if(is_array($ct['pay_way'])){
                $this->pay_way = implode(', ', $ct['pay_way']);
            }else{
                $this->pay_way = $ct['pay_way'];
            }
        }
        if(isset($ct['sender_phone'])){
            settype($ct['sender_phone'], 'string');
            $this->sender_phone = preg_replace("/[\D]/", '', $ct['sender_phone']);
        }
        $this->goods_id = isset($ct['goods_id']) ? $ct['goods_id'] : false;
        $this->pays_count = isset($ct['pays_count']) ? $ct['pays_count'] : false;

        $this->transData = $this->loadTransData();

        $this->validate();
        $this->notifyId = $this->logRq();
    }
    function convertStatus($statusCode = null){

        if($statusCode === null){
            $statusCode = $this->status;
        }

        switch($statusCode){
            case 'success':
                $r = 'success';
                break;
            case 'wait_secure':
                $r = 'wait';
                break;
            case 'failure';
                $r = 'error';
                break;
        }
        if(!isset($r)){
            $r = false;
        }
        return $r;
    }

    function loadTransData(){
        if(!$this->orderId){
            return array();
        }
        $q = "SELECT * FROM ".SYS_DATABASE.".`".\Verba\_mod('paysys_liqpay')->gC('transTable')."` WHERE "
            ."`orderId` = '".$this->orderId."'"
        ;
        $sqlr = $this->DB()->query($q);
        if(!$sqlr || !$sqlr->getNumRows()){
            $this->log->error('Transaction not found');
            return array();
        }
        return $sqlr->fetchRow();
    }

    function validate(){
        if($this->isValid === false){
            return false;
        }
        $i = 0;
        if(!$this->validateSignature()){
            --$i;
        }
        if(!is_array($this->transData)
            || !count($this->transData)
            || !array_key_exists('id', $this->transData)){
            $this->log->error('Transaction not found in log');
            --$i;
        }

        $fromRqSum = \Verba\reductionToCurrency($this->totalAmount);
        $fromRqCurrency = $this->currencyCode;
        $orderSum = \Verba\reductionToCurrency($this->orderData->getTopay());
        $orderCurrency = $this->currency->p('code');

        if(false === ($orderSum == $fromRqSum && $orderCurrency == $fromRqCurrency)){
            --$i;
            $this->log()->error('Request sum or currency mismatch');
        }

        $this->isValid = !($i < 0);

        return $this->isValid;
    }

    function validateSignature(){
        $str = $this->mCfg['signature'] . $this->responseData . $this->mCfg['signature'];
        $sign = base64_encode(sha1($str,1));

        if($sign == $this->signature){
            return true;
        }

        $this->log->error('Signature verification error. '.var_export($this, true));
        return false;
    }

    function isValid(){
        return $this->isValid;
    }

    function logRq(){
        $this->DB();
        $rqId = isset($this->transData['id']) ? $this->transData['id'] : '';
        $q = "INSERT INTO `".SYS_DATABASE."`.`".\Verba\_mod('paysys_liqpay')->gC('transLogTable')."` (
`created`,
`orderId`,
`rqId`,
`ip`,
`responseData`,
`validated`,
`log`,
`signature`
) VALUES (
  '".strftime("%Y-%m-%d %H:%M:%S")."',
  '".$this->DB->escape_string($this->orderId)."',
  '".$rqId."',
  '".ip2long(\Verba\getClientIP())."',
  '".$this->DB->escape_string(var_export($this->responseData, true))."',
  '".$this->isValid."',
  '".$this->log->getMessagesAsStr()."',
  '".$this->signature."'
)";
        $sqlr = $this->DB->query($q);
        if(!$sqlr){
            return false;
        }

        return $sqlr->getInsertId();
    }

    function updateTransactionByNotify(){
        $this->DB();
        if(!$this->transData['tranId']){
            $tranIdstr = "`tranId` = '".$this->DB->escape_string($this->tranId)."',";
        }else{
            $tranIdstr = '';
        }
        $q = "UPDATE `".SYS_DATABASE."`.`".$this->mCfg['transTable']."` SET
".$tranIdstr."
`updated` = '".strftime("%Y-%m-%d %H:%M:%S")."',
`status` = '".$this->DB->escape_string($this->status)."',
`code` = '".$this->DB->escape_string($this->code)."',
`notifyId` = '".$this->notifyId."',
`sender_phone` = '".$this->sender_phone."',
`pay_way` = '".$this->DB->escape_string($this->pay_way)."'
WHERE
`id` = '".$this->DB->escape_string($this->transData['id'])."'";

        $sqlr = $this->DB->query($q);
        if(!$sqlr || !$sqlr->getAffectedRows()){
            $this->log()->error('Unable to update Transaction rqId['.var_export($this->transData['id'], true).'], orderId:['.var_export($this->orderId, true).']');
            return false;
        }
        return $sqlr->getInsertId();
    }

}

