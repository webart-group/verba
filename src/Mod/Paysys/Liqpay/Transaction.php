<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 15:15
 */

namespace Verba\Mod\Paysys\Liqpay;

class Transaction extends \Verba\Base {

    protected $isValid;
    protected $_paysysCode = 'liqpay';
    protected $_modCode = 'liqpay';
    public $url;
    public $orderId;
    public $orderCode;
    public $currency;
    public $version;
    public $purchaseTime;
    public $purchaseDesc;
    public $sessionId;
    public $signature;
    public $orderData;
    public $mCfg;
    public $merchantId;
    public $status;
    public $code;

    function __construct($orderSid){
        $this->log();
        $this->loadOrderData($orderSid);
        if(is_object($this->orderData)){
            $this->orderId = $this->orderData->id;
            $this->orderCode = $this->orderData->code;
        }
        $this->paysys = \Verba\_mod('payment')->getPaysys($this->_paysysCode);
        $this->mCfg = \Verba\_mod('payment')->getPaysysMod($this->_modCode)->gC();
        $this->currency = $this->paysys->getCurrency($this->orderData->currencyId);
        $this->url = $this->mCfg['gatewayUrl'];
        $this->merchantId = $this->mCfg['merchantId'];
        $this->version = $this->mCfg['version'];
    }

    function setOrderData($orderData){
        if(!$orderData instanceof \Verba\Mod\Order\Model\Order){
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
    function loadOrderData($orderSid){
        $order = \Verba\_mod('order')->getOrder($orderSid);
        if(!$order){
            $this->orderData = false;
            $this->log->error('Order not found. id:'.var_export($orderUid, true));
            $this->isValid = false;
            return false;
        }
        $this->orderData = $order;
        return true;
    }

    function purchaseTimeToSql($val){
        if(!preg_match("/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/i", (string)$val, $_)){
            return 0;
        }

        $str = '20'.$_[1].'-'.$_[2].'-'.$_[3].' '.$_[4].':'.$_[5].':'.$_[6];
        return $str;
    }

    function getMsgByTranCode($tranCode){
        return \Verba\Lang::get('liqpay codes '.$tranCode);
    }

    function getStatusMsg(){
        $msg = (string)\Lang::get('liqpay codes '.$n->code);
        if($this->log->countMessages('error')){
            $msg .= "\nErrors:\n".$this->log->getMessagesAsString('error');
        }
    }
}