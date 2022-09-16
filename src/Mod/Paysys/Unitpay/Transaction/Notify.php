<?php

namespace Verba\Mod\Paysys\Unitpay\Transaction;

class Notify extends \Verba\Mod\Payment\Transaction\Receive
{

    protected $_paysysCode = 'unitpay';

    function __construct($orderId, $ct = false)
    {

        parent::__construct($orderId);

        if (!$ct) {
            $ct = &$_REQUEST;
        }
        $this->request = new \Verba\Mod\Payment\Request\Notify($this, $ct);

        $this->url = SYS_THIS_HOST . $_SERVER['REQUEST_URI'];

        $this->method = $this->request->method;
        $this->paymentId = $this->request->params['unitpayId'];

        $this->validate();
        $this->status = $this->genStatus();

        $this->createTx(array(
            'request' => $this->request->exportAsSerialized(),
            'description' => $this->description,
        ));
    }

    function handleRequest()
    {

        try {

            switch ($this->status) {
                case 'success':
                    if ($this->method == 'pay') {
                        $this->log()->event("Payment is successful. Order Id:" . $this->Order->getId());
                    } elseif ($this->method == 'check') {
                        $this->log()->event("Payment is checked. Order Id:" . $this->Order->getId());
                    }
                    break;
                case 'error':
                    $this->log()->error("Payment Notify error. Status: " . $this->status . ", Order Id:" . $this->Order->getId());
                    break;
                default:
                    $this->log()->error("Notify response unknown status " . var_export($this, true));
                    break;
            }

            if ($this->method == 'pay' || $this->method == 'error') {
                $this->mod->updateOrderStatus($this);
            }

            if (($this->method == 'check' || $this->method == 'pay')
                && $this->status == 'success') {
                $r = array('result' => array('message' => 'Запрос успешно обработан'));
            }
        } catch (\Exception $e) {
            $this->log()->error($e->getMessage() . "\n\nRequest:\n" . var_export($_REQUEST, true));
        }

        if (!isset($r)) {
            $r = array('error' => array('message' => 'Ошибка обработки запроса'));
        }

        return json_encode($r);
    }

    function successPayment()
    {
        return $this->method == 'pay' && $this->isValid() && $this->status == 'success';
    }

    function validate()
    {

//    $this->isValid = true;
//    return $this->isValid;

        parent::validate();

        if ($this->isValid === false) {
            return false;
        }
        $this->isValid = false;

        if ($this->Order->payed) {
            $this->description = 'Order already payed';
            return false;
        }

        if (!$this->validateSignature()) {
            $this->description = 'Signature verification error';
            return false;
        }

        if (!$this->paymentSum) {
            $this->description = 'Bad payment sum';
            return false;
        }

        $fromRqSum = \Verba\reductionToCurrency($this->request->params['orderSum']);
        $fromRqCurrency = $this->getPaysysMod()->convertIntCurCodeToOurCurCode($this->request->params['orderCurrency']);

        $orderCurrency = $this->currency->code;

        if ($this->paymentSum != $fromRqSum) {
            $this->description = 'Payment sum mismatch';
            return false;
        }

        if ($orderCurrency != $fromRqCurrency) {
            $this->description = 'Payment currency mismatch';
            return false;
        }

        if ($this->method == 'error') {
            $this->description = 'Payment marked as Error';
            return false;
        }
        if (!$this->paymentId) {
            $this->description = 'Bad Payment Id';
            return false;
        }
        $this->isValid = true;
        return $this->isValid;
    }

    function validateSignature()
    {
        $ourSigArr = $this->request->params;
        unset($ourSigArr['sign']);
        unset($ourSigArr['signature']);
        ksort($ourSigArr);

        array_push($ourSigArr, $this->mCfg['secret']);
        array_unshift($ourSigArr, $this->method);

        $ourSigArr['account'] = $this->Order->getCode();
        $ourSigArr['orderSum'] = $this->currency->toFixed($this->paymentSum);
        $ourSigArr['orderCurrency'] = $this->getCurIntCode();

        $ourSig = hash('sha256', join('{up}', $ourSigArr));

        if (strcasecmp($this->request->params['signature'], $ourSig) === 0) {
            return true;
        }
        $this->log->error('oursig:' . var_export($ourSig, true) . ', fromRequest:' . var_export($this->signature, true) . "\nsigArr: " . var_export($ourSigArr, true));
        return false;
    }

    function validateIp()
    {

        if (!is_array($this->mCfg['trustedIP']) || !count($this->mCfg['trustedIP'])) {
            return true;
        }

        $ip = ip2long(\Verba\getClientIP());
        foreach ($this->mCfg['trustedIP'] as $network) {
            $ip0 = ip2long($network . '.0');
            $ip255 = ip2long($network . '.255');
            if ($ip > $ip0 && $ip < $ip255) {
                return true;
            }
        }
        return false;
    }

}
