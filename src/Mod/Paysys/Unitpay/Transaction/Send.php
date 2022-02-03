<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 15:53
 */

namespace Mod\Paysys\Unitpay\Transaction;

class Send extends \Verba\Mod\Payment\Transaction\Send
{

    protected $_paysysCode = 'unitpay';
    /**
     * @var \Mod\Paysys\Unitpay
     */
    protected $mod;

    public $requestMethod = 'GET';
    public $method = 'gp'; // gopay

    function __construct($orderId)
    {

        parent::__construct($orderId);

        // $this->l(get_class($this).': constructor start');

        if (!empty($this->payTrans)) {

            // $this->l(get_class($this).': Trans found, попытка найти БилРеспонс в них');
            $BillResponse = $this->findSuccessCreateBillResponse();

        }

        // isValid в данном случае может быть true только при наличии
        // успешного создания Счета
        if (isset($BillResponse) && is_array($BillResponse) && count($BillResponse)) {

            // $this->l(get_class($this).': БилРеспонс массив'.var_export($BillResponse, true));
            // $this->l(get_class($this).': handleCreateBillResponse');

            list(
                $this->isValid,
                $this->description,
                $this->paymentId,
                $this->url,
                ) = CreateBill::handleCreateBillResponse($BillResponse);

            $this->logState();

        } else {

            // $this->l('Create bill request');

            $CreateBillRq = new CreateBill($this->Order);
            // $this->l('created. Inherit props');

            $this->isValid = $CreateBillRq->isValid();
            $this->paymentId = $CreateBillRq->paymentId;
            $this->description = $CreateBillRq->description;
            $this->url = $CreateBillRq->_billUrl;
        }

        // $this->l('create Tx');

        $this->createTx(array(
            'url' => $this->url,
            'description' => $this->description,
        ));

        // $this->l('Tx id: ', $this->_Tx->getId());

        // $this->l('constructor complete');
    }

    function findSuccessCreateBillResponse()
    {

        if (!is_array($this->payTrans) || !count($this->payTrans)) {
            return false;
        }

        $successTran = false;
        foreach ($this->payTrans as $Tx) {
            if (!is_object($Tx)
                || !$Tx instanceof \Model\Item
                || $Tx->io != '0'
                || $Tx->status != 'success'
                || $Tx->method != 'cb' /* createBill*/) {
                continue;
            }
            $successTran = $Tx;
            break;
        }

        if (!$successTran) {
            return false;
        }
        return unserialize($successTran->response);

    }

}
