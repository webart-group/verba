<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 15:58
 */
class PayTransaction_UPC extends Base{

    protected $_paysysCode = 'upc';
    protected $_modCode = 'upc';

    protected $upcCurrency;
    protected $isValid;
    public $url;
    public $orderId;
    public $orderCode;
    public $xid;
    public $tranCode;
    public $rrn;
    public $approvalCode;
    public $proxyPan;
    /**
     * @var \Verba\Model\Currency
     */
    public $currency;
    public $totalAmount;
    public $version;
    public $locale;
    public $purchaseTime;
    public $altCurrency;
    public $altTotalAmount;
    public $purchaseDesc;
    public $sessionId;
    public $signatureData;
    public $signatureHash;
    public $base64sign;
    public $delay;
    public $orderData;
    public $mCfg;
    public $merchantId;
    public $terminalId;
    public $reason;

    function __construct($orderSid){
        $this->log();
        $this->loadOrderData($orderSid);
        if(is_object($this->orderData)){
            $this->orderId = $this->orderData->id;
            $this->orderCode = $this->orderData->code;
        }
        $this->paysys = \Verba\_mod('payment')->getPaysys($this->_paysysCode);
        $this->mCfg = \Verba\_mod('payment')->getPaysysMod($this->_modCode)->gC();
        $this->upcCurrency = $this->paysys->getCurrency($this->mCfg['upcCurrencyId']);
        $this->url = $this->mCfg['gatewayUrl'];
        $this->merchantId = $this->mCfg['merchantId'];
        $this->terminalId = $this->mCfg['terminalId'];
        $this->version = $this->mCfg['version'];
        $this->delay = $this->mCfg['delay'];
        $this->currency = $this->upcCurrency->p('codeNum');
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

    function getSignatureData(){
        return $this->signatureData;
    }
    function genSignatureHash(){
        $d = $this->getSignatureData();
        if(!is_string($d)){
            return false;
        }
        return md5($d);
    }

    function setReason($val){
        $this->reason = (string)$val;
    }

    function getReason(){
        return $this->reason;
    }

    function purchaseTimeToSql($val){
        if(!preg_match("/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/i", (string)$val, $_)){
            return 0;
        }

        $str = '20'.$_[1].'-'.$_[2].'-'.$_[3].' '.$_[4].':'.$_[5].':'.$_[6];
        return $str;
    }
}

class PaySend_UPC extends PayTransaction_UPC{

    function __construct($orderSid){
        parent::__construct($orderSid);

        $this->totalAmount = \Verba\reductionToCurrency($this->orderData->topay * $this->upcCurrency->p('rate')) * $this->upcCurrency->p('scale');

        if($this->upcCurrency->getId() != $this->orderData->currencyId){
            $this->altCurrencyData = $this->paysys->getCurrency($this->orderData->currencyId);
            $this->altTotalAmount = \Verba\reductionToCurrency($this->orderData->topay * $this->altCurrencyData->p('rate')) * $this->altCurrencyData->p('scale');
            $this->altCurrency = $this->altCurrencyData->p('codeNum');
        }
        $this->purchaseTime = date('ymdHis');
        $this->sessionId = session_id();
        $this->signatureData = $this->genSignatureData();
        $this->base64sign = $this->genBase64sign();
        $this->purchaseDesc = $this->orderData->description;
        $this->locale = $this->genLocale();

        $this->signatureHash = $this->genSignatureHash();
    }

    function genSignatureData(){

        $r = $this->merchantId.';'
            .$this->terminalId.';'
            .$this->purchaseTime.';'
            .$this->orderCode;
        if($this->delay){
            $r .= ','.(int)((bool)$this->delay);
        }
        $r .=';';
        $r .= $this->currency;
        if($this->altCurrency){
            $r .= ','.$this->altCurrency;
        }
        $r .=';';

        $r .= $this->totalAmount;
        if($this->altTotalAmount){
            $r .= ','.$this->altTotalAmount;
        }
        $r .=';';

        $r .= $this->sessionId.';';
        return $r;
    }

    function genBase64sign(){
        $mUpc = \Verba\_mod('paysys_upc');
        $filepath = $mUpc->getPath().'/'.$this->merchantId.'.pem';
        if(!is_readable($filepath)){
            $this->log()->error('Unable to read UPC merchant public key file');
            return false;
        }
        $fp = fopen($filepath, 'r');
        $priv_key = fread($fp, 8192);
        fclose($fp);
        $pkeyid = openssl_get_privatekey($priv_key);
        openssl_sign($this->signatureData, $signature, $pkeyid);
        openssl_free_key($pkeyid);
        return base64_encode($signature);
    }

    function genLocale(){
        return SYS_LOCALE;
    }

    function logRq(){
        $q = "INSERT INTO `".SYS_DATABASE."`.`".\Verba\_mod('paysys_upc')->gC('transTable')."` (
`purchaseTime`,
`orderId`,
`totalAmount`,
`currency`,
`currencyId`,
`altTotalAmount`,
`altCurrency`,
`altCurrencyId`,
`description`,
`owner`,
`version`,
`signature64sign`,
`signatureData`,
`delay`,
`sd`,
`terminalId`,
`merchantId`,
`signatureHash`,
`locale`
) VALUES (
  '".$this->purchaseTimeToSql($this->purchaseTime)."',
  '".$this->orderId."',
  '".$this->totalAmount."',
  '".$this->currency."',
  '".$this->upcCurrency->getId()."',
  '".$this->altTotalAmount."',
  '".($this->altCurrency ? $this->altCurrency : '')."',
  '".(is_object($this->altCurrencyData) ? $this->altCurrencyData->getId() : '')."',
  '".$this->purchaseDesc."',
  '".User()->getID()."',
  '".$this->version."',
  '".$this->base64sign."',
  '".$this->signatureData."',
  '".$this->delay."',
  '".$this->sessionId."',
  '".$this->terminalId."',
  '".$this->merchantId."',
  '".$this->signatureHash."',
  '".$this->locale."'
)";
        $sqlr = $this->DB()->query($q);
        if(!$sqlr){
            return false;
        }
        return $sqlr->getInsertId();
    }
}

class PayNotify_UPC extends PayTransaction_UPC{
    public $transData = array();

    function __construct($orderSid, $ct = false){
        parent::__construct($orderSid);
        if(!$ct){
            $ct = &$_REQUEST;
        }
        $this->purchaseTime = isset($ct['PurchaseTime']) ? $ct['PurchaseTime'] : false;

        $this->xid = isset($ct['XID']) ? $ct['XID'] : false;
        $this->rrn = isset($ct['Rrn']) ? $ct['Rrn'] : false;
        $this->proxyPan = isset($ct['ProxyPan']) ? $ct['ProxyPan'] : false;
        $this->approvalCode = isset($ct['ApprovalCode']) ? $ct['ApprovalCode'] : false;
        $this->tranCode = isset($ct['TranCode']) ? $ct['TranCode'] : false;
        $this->currency = isset($ct['Currency']) ?  $ct['Currency'] : false;
        $this->totalAmount = isset($ct['TotalAmount']) ?  $ct['TotalAmount'] : false;
        $this->altTotalAmount = isset($ct['AltTotalAmount']) ?  $ct['AltTotalAmount'] : false;
        $this->altCurrency = isset($ct['AltCurrency']) ?  $ct['AltCurrency'] : false;
        $this->sessionId = isset($ct['SD']) ?  $ct['SD'] : false;
        $this->delay = isset($ct['Delay']) ? $ct['Delay'] : false;
        $this->signatureData = $this->genSignatureData();
        $this->base64sign = isset($ct['Signature']) ? $ct['Signature'] : false;

        $this->signatureHash = $this->genSignatureHash();

        $this->transData = $this->loadTransData();

        $this->validate();
        $this->logRq();
    }

    function genSignatureData(){

        $r = $this->merchantId.';'
            .$this->terminalId.';'
            .$this->purchaseTime.';'
            .$this->orderCode;
        if($this->delay){
            $r .= ','.(int)((bool)$this->delay);
        }
        $r .=';';
        $r .= $this->xid;
        $r .=';';
        $r .= $this->currency;
        if($this->altCurrency){
            $r .= ','.$this->altCurrency;
        }
        $r .=';';

        $r .= $this->totalAmount;
        if($this->altTotalAmount){
            $r .= ','.$this->altTotalAmount;
        }
        $r .=';';
        $r .= $this->sessionId;
        $r .=';';
        $r .= $this->tranCode;
        $r .=';';
        $r .= $this->approvalCode;
        $r .=';';
        return $r;
    }

    function loadTransData(){
        if(!$this->purchaseTime || !$this->orderId){
            return array();
        }
        $datetime = $this->purchaseTimeToSql($this->purchaseTime);
        $q = "SELECT * FROM ".SYS_DATABASE.".`".\Verba\_mod('paysys_upc')->gC('transTable')."` WHERE "
            ."`orderId` = '".$this->orderId."'"
            ." && `purchaseTime` = '".$datetime."'"
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

        $this->isValid = !($i < 0);

        return $this->isValid;
    }

    function validateSignature(){
        $signature = base64_decode($this->base64sign) ;
        $mUpc = \Verba\_mod('paysys_upc');

        $filepath = $mUpc->getPath().'/'.$this->mCfg['server_crt_filename'];
        if(!is_readable($filepath)){
            $this->log()->error('Unable to read UPC server certificate file');
            return false;
        }
        $fp = fopen($filepath, 'r');
        $cert = fread($fp, 8192);
        fclose($fp);
        $pubkeyid = openssl_get_publickey($cert);
        $data = $this->genSignatureData();
        $r = openssl_verify($data, $signature, $pubkeyid);
        openssl_free_key($pubkeyid);

        if($r == 1){
            return true;
        }elseif($r == 0){
            $this->log->error('Invalid signature. ssl_err['.openssl_error_string().']');
        }else{
            $this->log->error('Signature verification error. ssl_err['.openssl_error_string().']');
        }
        return false;
    }

    function isValid(){
        return $this->isValid;
    }

    function logRq(){
        $this->DB();
        $rqId = isset($this->transData['id']) ? $this->transData['id'] : '';
        $q = "INSERT INTO `".SYS_DATABASE."`.`".\Verba\_mod('paysys_upc')->gC('transLogTable')."` (
`created`,
`orderId`,
`rrn`,
`rqId`,
`ip`,
`request`,
`validated`,
`log`,
`signatureData`,
`signatureHash`
) VALUES (
  '".strftime("%Y-%m-%d %H:%M:%S")."',
  '".$this->DB->escape_string($this->orderId)."',
  '".$this->DB->escape_string($this->rrn)."',
  '".$rqId."',
  '".long2ip(\Verba\getClientIP())."',
  '".$this->DB->escape_string(var_export($_REQUEST, true))."',
  '".$this->isValid."',
  '".$this->log->getMessagesAsStr()."',
  '".$this->signatureData."',
  '".$this->signatureHash."'
)";
        $sqlr = $this->DB->query($q);
        if(!$sqlr){
            return false;
        }
        return $sqlr->getInsertId();
    }

    function getErrorsAsReason(){
        $msg = $this->log->getMessages('error');
        if(!is_array($msg)){
            return '';
        }
        $msg = mb_substr(implode("\n ", $msg), 0, 125);
        return $msg;
    }
}

class OrderTransUPC extends \Verba\Mod\Order\Transaction{

}