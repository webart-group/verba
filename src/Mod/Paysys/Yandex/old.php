<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 16:01
 */

class PayTransaction_Yandex extends Base{

    public $payRqId;
    protected $_paysysCode = 'yandex';
    protected $_modCode = 'yandex';
    public $payTrans = array();
    public $orderId;
    public $orderCode;
    public $totalAmount;
    public $purchaseTime;
    public $purchaseDesc;
    public $sessionId;
    public $signature;
    public $orderData;
    public $mCfg;
    public $receiver;
    public $status;
    public $code;

    public $extParams = array(

    );
    public $currency;

    function __construct($orderId){
        $this->log();
        sort($this->extParams);
        $this->loadOrderData($orderId);
        if(is_object($this->orderData)){
            $this->orderId = $this->orderData->id;
            $this->orderCode = $this->orderData->code;
        }
        $this->paysys = \Verba\_mod('payment')->getPaysys($this->_paysysCode);
        $this->mCfg = \Verba\_mod('payment')->getPaysysMod($this->_modCode)->gC();

        $this->currency = \Verba\_mod('currency')->getCurrency($this->orderData->currencyId);
        $this->receiver = $this->mCfg['receiver'];

        $this->sessionId = session_id();
    }

    function setOrderData($orderData){
        if(!$orderData instanceof \Mod\Order\Model\Order){
            return false;
        }
        $this->orderData = $orderData;
    }
    function getOrderData(){
        if($this->orderData === null){
            $this->loadOrderData();
        }
        return $this->orderData;
    }
    function loadOrderData($orderUid){
        $order = \Verba\_mod('order')->getOrder($orderUid);
        if(!$order){
            $this->orderData = false;
            $this->log->error('Order not found');
            $this->isValid = false;
            return false;
        }
        $this->orderData = $order;
        return true;
    }

    function setOrderCode($val){
        $val = (string)$val;
        if(!$val || $this->orderCode !== null){
            return false;
        }
        $this->orderCode = $val;
    }

    function setPayRqId($val){
        $val = (int)$val;
        if(!$val || $this->payRqId !== null){
            return false;
        }

        $this->payRqId = $val;
    }

    function purchaseTimeToSql($val){
        if(!preg_match("/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/i", (string)$val, $_)){
            return 0;
        }

        $str = '20'.$_[1].'-'.$_[2].'-'.$_[3].' '.$_[4].':'.$_[5].':'.$_[6];
        return $str;
    }

    function getMsgByTranCode($tranCode){
        return \Verba\Lang::get('yandex codes '.$tranCode);
    }

    function loadPayTrans(){
        $r = array();
        if(!$this->orderId){
            return $r;
        }
        $q = "SELECT * FROM ".SYS_DATABASE.".`".$this->mCfg['payLogTable']."` WHERE "
            ."`orderId` = '".$this->orderId."'"
        ;
        $sqlr = $this->DB()->query($q);
        if(!$sqlr || !$sqlr->getNumRows()){
            return $r;
        }

        return $sqlr->fetchRow();
    }

    function addExtsToArray(&$r){
        \Verba\reductionToArray($r);
        if(!count($this->extParams)){
            return;
        }

        foreach($this->extParams as $pName){
            $r[$pName] = $this->$pName;
        }
    }
}

class PaySend_Yandex extends PayTransaction_Yandex{
    public $requestData;
    public $url;

    function __construct($orderId){
        parent::__construct($orderId);

        $this->url = $this->mCfg['paymentUrl'];
        $this->totalAmount = \Verba\reductionToCurrency($this->orderData->getTopay());
        $this->purchaseTime = date('ymdHis');

        $this->purchaseFormComment = htmlspecialchars(Lang::get('yandex formcomment').' '.Lang::get('order shopName'));
        $this->purchaseDesc = htmlspecialchars(Lang::get('order invoiceText', array('invCode' =>  $this->orderCode)));

        $this->payRqId = $this->logRq();
        $this->requestData = $this->genRequestData();
        $this->updateLog();
    }

    function genRequestData(){

        $formcomment = mb_substr($this->purchaseFormComment,0,50);
        $desc = mb_substr($this->purchaseDesc,0,150);

        $data = array(
            'receiver' => $this->receiver,
            'formcomment' => $formcomment,
            'short-dest' => $formcomment,
            'writable-targets' => false,
            'quickpay-form' => 'shop',
            'targets' => $desc,
            'sum' => $this->totalAmount,
            'label' => $this->orderCode
        );

        $this->addExtsToArray($data);
        return $data;
    }

    function logRq(){
        $q = "INSERT INTO `".SYS_DATABASE."`.`".$this->mCfg['payLogTable']."` (
`purchaseTime`,
`orderId`,
`totalAmount`,
`description`,
`owner`
) VALUES (
  '".$this->purchaseTimeToSql($this->purchaseTime)."',
  '".$this->orderId."',
  '".$this->totalAmount."',
  '".$this->DB()->escape_string($this->purchaseDesc)."',
  '".User()->getID()."'
)";
        $sqlr = $this->DB()->query($q);
        if(!$sqlr){
            $error = $this->DB()->getLastError();
            $this->log()->error('Unable to create PaySent request log entry. SQL-error:'.var_export($error, true));
            return false;
        }
        return $sqlr->getInsertId();
    }

    function updateLog(){
        $q = "UPDATE `".SYS_DATABASE."`.`".$this->mCfg['payLogTable']."` SET
`requestData` = '".$this->DB()->escape_string(var_export($this->requestData, true))."'
WHERE
`id` = '".$this->payRqId."'
&& `orderId` = '".$this->orderId."'";

        $sqlr = $this->DB()->query($q);
        if(!$sqlr){
            $error = $this->DB()->getLastError();
            $this->log()->error('Unable to update PaySent request log entry. SQL-error:'.var_export($error, true));
            return false;
        }
        return true;
    }

}

class PayNotify_Yandex extends PayTransaction_Yandex{

    public $responseData = '';
    protected $isValid;
    public $status;
    public $statusMsg;
    public $notifyId;

    function __construct($orderId, $ct = false){
        parent::__construct($orderId);

        if(!$this->orderId){
            throw new Exception('Notify response data does not contain required data. Request'."\n".var_export($ct, true));
        }

        if(!$ct){
            $ct = &$_REQUEST;
        }
        $this->responseData = $ct;

        $this->signature = $ct['sha1_hash'];
        $this->totalAmount = \Verba\reductionToCurrency($ct['withdraw_amount']);

        $this->payTrans = $this->loadPayTrans();

        $this->validate();
        $this->status = $this->genStatus();
        $this->notifyId = $this->logRq();

    }

    function genStatus(){
        if(!$this->isValid){
            return 'not_valid';
        }
        if(isset($this->payTrans['status'])
            && $this->payTrans['status'] == 'success'){
            $this->statusMsg = 'Secondary Notify for existing success pay status';
            return 'not_valid';
        }

        return 'success';
    }

    function validate(){
        if($this->isValid === false){
            return false;
        }
        $i = 0;
        if(!$this->validateSignature()){
            --$i;
        }

        if(!is_array($this->payTrans)
            || !count($this->payTrans)
        ){
            $this->statusMsg = 'Pay Requests not found for order ['.$this->orderCode.']';
            $this->log->error($this->statusMsg);
            --$i;
        }

        $reqSum = \Verba\reductionToCurrency($this->orderData->getTopay());
        $notifySum = $this->totalAmount; // withdraw_amount

        if($reqSum != $notifySum){
            $this->statusMsg = 'Notify summ is mismatch';
            $this->log->error('reqSum: '.$reqSum.', notifySum:'.$notifySum);
            --$i;
        }

        $this->isValid = !($i < 0);

        return $this->isValid;
    }

    function validateSignature(){
        $toValidate = array(
            $this->responseData['notification_type'],
            $this->responseData['operation_id'],
            $this->responseData['amount'],
            $this->responseData['currency'],
            $this->responseData['datetime'],
            $this->responseData['sender'],
            $this->responseData['codepro'],
            $this->mCfg['secret'],
            $this->responseData['label'],
        );

        $toValidate = implode('&',$toValidate);

        $toValidate = sha1($toValidate);
        if(strcasecmp($this->signature, $toValidate) === 0){
            return true;
        }
        $this->statusMsg = 'Signature verification error';
        $this->log->error('generated sig:'.var_export($toValidate, true). ', fromRequest:'.var_export($sign, true));
        return false;
    }

    function validateIp(){

        if(!is_array($this->mCfg['trustedIP']) || !count($this->mCfg['trustedIP'])){
            return true;
        }

        $ip = ip2long(\Verba\getClientIP());
        foreach($this->mCfg['trustedIP'] as $network){
            $ip0 = ip2long($network.'.0');
            $ip255 = ip2long($network.'.255');
            if($ip > $ip0 && $ip < $ip255){
                return true;
            }
        }
        return false;
    }

    function isValid(){
        return $this->isValid;
    }

    function logRq(){
        $this->DB();

        $f = array(
            'created' => strftime("%Y-%m-%d %H:%M:%S"),
            'orderId' => $this->orderId,
            'ip' => ip2long(\Verba\getClientIP()),
            'responseData' => var_export($this->responseData, true),
            'validated' => $this->isValid,
            'signature' => $this->signature,
            'status' => $this->status,
            'statusMsg' => $this->statusMsg,
            'log' => $this->log->getMessagesAsStr(),
        );
        $fieldsNames = $fieldsValues = '';
        foreach($f as $fName => $fValue) {
            $fieldsNames .= '`'.$fName.'`,';
            $fieldsValues .= "'".$this->DB->escape_string($fValue)."',";
        }

        $q = "INSERT INTO `".SYS_DATABASE."`.`".$this->mCfg['notifyLogTable']."` (
    ".substr($fieldsNames, 0, -1)."
    ) VALUES (
    ".mb_substr($fieldsValues, 0, -1)."
    )";

        $sqlr = $this->DB->query($q);
        if(!$sqlr){
            $error = $this->DB()->getLastError();
            $this->log()->error('Unable to create Notify log entry. SQL-error:'.var_export($error, true));
            return false;
        }

        return $sqlr->getInsertId();
    }

    function updateTransactionByNotify(){
        $this->DB();
        if(!$this->payRqId){
            $this->log()->error('Unable to update Pay Request log entry - payRqId is empty. '.var_export($this, true));
            return false;
        }

        $q = "UPDATE `".SYS_DATABASE."`.`".$this->mCfg['payLogTable']."` SET
`updated` = '".strftime("%Y-%m-%d %H:%M:%S")."',
`status` = '".$this->DB->escape_string($this->status)."',
`notifyId` = '".$this->notifyId."',
`payer_wm` = '".$this->DB->escape_string($this->payer_wm)."',
`payer_purse` = '".$this->DB->escape_string($this->payer_purse)."'
WHERE
`id` = '".$this->DB->escape_string($this->payRqId)."'
&& `orderId` = '".$this->orderId."'";

        $sqlr = $this->DB->query($q);
        if(!$sqlr || !$sqlr->getAffectedRows()){
            $this->log()->error('Unable to update Pay Request log entry payRqId. '.var_export($this, true));
            return false;
        }
        return $sqlr->getInsertId();
    }

    function getStatusMsg(){
        return $this->statusMsg;
    }

}

class OrderTransYandex extends \Verba\Mod\Order\Transaction{

}

