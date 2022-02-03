<?php

namespace Mod\Paysys\Unitpay\Transaction;

class CreateBill extends \Verba\Mod\Payment\Transaction\Send
{

    protected $_paysysCode = 'unitpay';
    public $method = 'cb'; // createBill
    public $_billUrl; // createBill

    function __construct($orderId)
    {
        parent::__construct($orderId);
        // $this->l('Constructor start');

        if (preg_match("/^unitpay(.*)$/", $this->Order->getPaysys()->code, $_buf)) {
            switch (strtolower($_buf[1])) {
                case 'qiwi':
                    $paymentType = 'qiwi';
                    if (!isset($_REQUEST['qiwi_client_account']) || !is_numeric($_REQUEST['qiwi_client_account'])) {
                        throw  new \Verba\Exception\Building('Bad params');
                    }
                    $extended_params = array(
                        'params[phone]' => $_REQUEST['qiwi_client_account'],
                    );
                    break;
                case 'yandex':
                    $paymentType = 'yandex';
                    break;
            }
        }

        if (!isset($paymentType)) {
            $paymentType = 'card';
        }

        $this->request = new \Mod\Payment\Request\Send($this, array(
                'method' => 'initPayment',
                'params[paymentType]' => $paymentType,
                'params[desc]' => urlencode($this->Order->getBillTitle()),
                'params[sum]' => $this->paymentSum,
                'params[account]' => $this->Order->getCode(),
                'params[projectId]' => $this->mCfg['projectId'],
                'params[secretKey]' => $this->mCfg['secret'],
                'params[ip]' => \Verba\getClientIP(),
                'params[currency]' => $this->getCurIntCode(),
                'params[resultUrl]' => urlencode($this->Order->getStatusUrl())
            )
        );

        if (isset($extended_params)) {
            $this->request->applyConfigDirect($extended_params);
        }
        $querystr = '';
        foreach ($this->request->getFields() as $pkey => $v) {
            $querystr .= '&' . $pkey . '=' . $v;
        }

        $this->url = 'https://unitpay.ru/api?' . substr($querystr, 1);


        // $this->l('file_get_contents url - ', $this->url);
        $rawResp = file_get_contents($this->url, true);
        // $this->l('last error - ', error_get_last());
        // $this->l('rawResp - ', $rawResp);

        if (is_string($rawResp) && !empty($rawResp)) {
            // $this->l('rawResp is string');
            $this->response = json_decode($rawResp, true);
            // $this->l('decoded response', $this->response);
            if (!$this->response || !is_array($this->response)) {
                // $this->l('decoded response is bad');
                $this->response = $rawResp;
            } else {
                // $this->l('decoded response is good');
                list(
                    $this->isValid,
                    $this->description,
                    $this->paymentId,
                    $this->_billUrl,
                    ) = self::handleCreateBillResponse($this->response);

                // $this->l();
            }
        }
        unset($rawResp);
        // $this->l('create Tx');
        $this->createTx(array(
            'url' => $this->url,
            'request' => $this->request->exportAsSerialized(),
            'response' => serialize($this->response),
        ));

        // $this->l('constructor complete');
    }

    /**
     * @param $BillResponse
     * @return array
     */
    static public function handleCreateBillResponse($BillResponse)
    {
        $r = array(
            0 => false, // isValid
            1 => null, // description
            2 => null, // paymentId
            3 => null, // redirectUrl
        );
        $r[0] = false;
        if (!is_array($BillResponse)
            || (!isset($BillResponse['result'])
                && !isset($BillResponse['error'])
            )) {
            $r[1] = \Verba\Lang::get('unitpay messages generalError');

        } elseif (isset($BillResponse['result'])) {

            $r[0] = true;
            $r[2] = $BillResponse['result']['paymentId'];
            $r[1] = $BillResponse['result']['message'];

            if ($BillResponse['result']['type'] == 'redirect') {
                $r[3] = $BillResponse['result']['redirectUrl'];
            }

        } elseif (isset($BillResponse['error'])) {

            $r[1] = $BillResponse['error']['message'];

        }
        return $r;
    }

}
