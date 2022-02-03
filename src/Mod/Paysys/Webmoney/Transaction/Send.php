<?php

namespace Mod\Paysys\Webmoney\Transaction;

class Send extends \Verba\Mod\Payment\Transaction\Send
{
    protected $_paysysCode = 'webmoney';

    /**
     * @var \Mod\Paysys\Webmoney
     */
    protected $mod;

    public $simMode;

    protected $merchantPurse;

    function __construct($orderId)
    {
        parent::__construct($orderId);

        $this->url = $this->mCfg['paymentUrl'];

        $this->merchantPurse = $this->mod->getMerchantPurse($this->currency->getCode());

        if (isset($this->mCfg['simMode']) && $this->mCfg['simMode'] !== null && $this->mCfg['simMode'] !== false) {
            $this->simMode = (int)$this->mCfg['simMode'];
            $this->testMode = 1;
        }

        $this->request = new \Mod\Payment\Request\Send($this, $this->genRequestData());

        $this->validate();
        $this->status = $this->genStatus();

        $this->createTx(array(
            'request' => $this->request->exportAsSerialized(),
            'status' => $this->status,
            'description' => $this->description,
        ));
    }

    function genRequestData()
    {

        $purchaseUrl = $this->Order->getUrlPurchase();

        $data = array(
            'LMI_PAYEE_PURSE' => $this->merchantPurse,
            'LMI_SUCCESS_URL' => $purchaseUrl,
            'LMI_FAIL_URL' => $purchaseUrl,
            'LMI_PAYMENT_AMOUNT' => $this->paymentSum,
            'LMI_PAYMENT_DESC_BASE64' => base64_encode($this->Order->getBillTitle()),
            'orderCode' => $this->Order->getCode(),
        );
        if ($this->Order->email) {
            $data['LMI_PAYMER_EMAIL'] = $this->Order->email;
        }

        if (is_int($this->simMode)) {
            $data['LMI_SIM_MODE'] = $this->simMode;
        }

        return $data;
    }

    function validate()
    {

        if (!($this->isValid = parent::validate())) {
            return $this->isValid;
        }

        $this->isValid = false;

        if ($this->Order->payed) {
            $this->description = 'Order already payed';
            return false;
        }

        if (!$this->merchantPurse) {
            $this->description = 'Unknown Merchant param';
            return $this->isValid;
        }
        if (!$this->paymentSum || $this->paymentSum != $this->Order->gatewayPaymentSum()) {
            $this->description = 'Payment sum error';
            return $this->isValid;
        }

        $this->isValid = true;

        return $this->isValid;
    }
}
