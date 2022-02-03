<?php
namespace Mod\Paysys_Webmoney;

class PayNotify extends \PaymentTransactionReceive{

  /**
   * @var \Mod\Paysys_Webmoney
   */
  protected $mod;
  protected $_paysysCode = 'webmoney';

  public $notifyId;

  protected $_signFields;

  public $prerequest;


  function __construct($orderId, $ct = false){

    parent::__construct($orderId);

    if(!$this->isOrderValid()){
      throw new \Exception('Notify response data does not contain required data.');
    }

    if(!$ct){
      $ct = &$_REQUEST;
    }


    $this->prerequest = isset($ct['LMI_PREREQUEST']) ? intval((bool)$ct['LMI_PREREQUEST']) : 0;

    $this->method = $this->prerequest ? 'check' : 'pay';

    $this->request = new PayNotifyRequest($this, $ct);

    $this->validate();
    $this->status = $this->genStatus();

    $this->createTx(array(
      'request' => $this->request->exportAsSerialized(),
      'status' => $this->status,
      'description' => $this->description,
    ));
  }

  function handleRequest(){

    try{

      switch($this->status){
        case 'success':
          if($this->prerequest){
            $this->log()->event("Payment prerequest successful. Order Id:".$this->Order->getId());
          }else{
            $this->log()->event("Payment success. Order Id:".$this->Order->getId());
          }
          break;
        case 'error':
          $this->log()->error("Payment Notify error. Order Id:".$this->Order->getId());
          break;
        default:
          $this->log()->error("Notify response unknown status");
          break;
      }

      if($this->method == 'pay'){
        $this->mod->updateOrderStatus($this);
      }

      if($this->method == 'check'){
        $r = $this->status == 'success' ? 'YES' : $this->description;
      }else{
        $r = $this->status == 'success' ? '' : $this->description;
      }

    }catch(\Exception $e){
      $this->log()->error($e->getMessage());
    }

    if(!isset($r)){
      $r = 'error';
    }

    return $r;
  }

  function successPayment()
  {
    return $this->method == 'pay' && $this->isValid() && $this->status == 'success';
  }

  function validate(){

//    $this->isValid = true;
//    return $this->isValid;

    if($this->isValid === false){
      return false;
    }

    $this->isValid = parent::validate();
    if(!$this->isValid){
      $this->isValid;
    }

    $this->isValid = false;

    if(!$this->validateIp()){
      $this->description = 'Bad IP';
      return false;
    }

    if($this->paymentSum != $this->request->payment_amount){
      $this->description = 'Payment sum error';
      $this->log->error('reqSum: '.$this->paymentSum.', notifySum:'.$this->request->payment_amount);
      return false;
    }

    $validateMethod = '_validate'.ucfirst($this->method);

    return $this->$validateMethod();
  }

  protected function _validateCheck(){

    $this->isValid = true;

    return $this->isValid;

  }

  protected function _validatePay(){

    $this->isValid = false;

    if($this->Order->payed){
      $this->description = 'Order already payed';
      return false;
    }

    if(!$this->validateSignature()){
      $this->description = 'Signature verification error';
      return false;
    }

    $this->isValid = true;

    return $this->isValid;
  }

  function genSignature(){
    if(!is_array($this->_signFields) ){
      $this->_signFields = array(
        $this->mod->getMerchantPurse($this->currency->getCode()),
        $this->currency->toFixed($this->paymentSum),
        $this->request->payment_no,
        $this->request->mode,
        $this->request->sys_invs_no,
        $this->request->sys_trans_no,
        $this->request->sys_trans_date,
        $this->mCfg['pass'],
        $this->request->payer_purse,
        $this->request->payer_wm,
      );
    }


    $r = implode(';',$this->_signFields);
    return strtoupper(hash('sha256', $r));
  }

  function validateSignature(){
    $r = $this->genSignature();

    if($this->request->hash2
    && $r === $this->request->hash2){
      return true;
    }

    $this->log->error('generated sig:'.var_export($r, true)
      . ', fromRequest:'.var_export($this->request->hash2, true)
      . ', signFields: \n'.var_export($this->_signFields, true)
    );
    return false;
  }

  function validateIp(){

    return true;

    if(!is_array($this->mCfg['trustedIP']) || !count($this->mCfg['trustedIP']) || !SYS_IS_PRODUCTION){
      return true;
    }

    $clientIP = \Verba\getClientIP();
    $ipLong = ip2long($clientIP);
    foreach($this->mCfg['trustedIP'] as $network){
      $ip0 = ip2long($network.'.0');
      $ip255 = ip2long($network.'.255');
      if($ipLong > $ip0 && $ipLong < $ip255){
        return true;
      }
    }

    $this->log()->error('Untrusted ip: '.$clientIP);

    return false;
  }

}

class PayNotifyRequest extends \Verba\Mod\Paysys\Payment\Request\Notify {

  function extractRequestFields($ct){

    if(!is_array($ct) || !count($ct)){
      return false;
    }

    $cfg = array();
    foreach($ct as $key => $value){
      if(preg_match("/^LMI_([A-Z_0-9]+)$/", $key, $_buf)){
        $rkey = strtolower($_buf[1]);
      }else{
        $rkey = $key;
      }
      $cfg[$rkey] = $value;
    }

    return $cfg;
  }

  function setMode($val){
    $this->fields['mode'] = (int)((bool)$val);
  }

  function setPayment_amount($val){
    $this->fields['payment_amount'] = $val;
  }

}