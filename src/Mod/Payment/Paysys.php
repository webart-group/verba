<?php

namespace Mod\Payment;

use Mod\Paysys\Unitpay\Transaction\Notify;

trait Paysys
{

    //public $pscode;

    //protected $protocol = null;

    //protected $_tx_code = 'tx';
    /**
     * @var \Model
     */
    //protected $_tx;

    function init()
    {
        if ($this->_tx === null) {
            $this->_tx = \Verba\_oh(is_string($this->_tx_code)
                ? $this->_tx_code : 'tx');
        }

        if(!$this->_tx_code) {
            $this->_tx_code = $this->_tx->getCode();
        }
    }

    function getPayTransactionHandler()
    {
        $rfc = new \ReflectionClass($this);
        return '\\'.$rfc->getName().'\\Transaction\\Send';
    }

    function getTxCode()
    {
        return $this->_tx_code;
    }

    function getPaysysAcpExtraTabs()
    {

        return $this->gC('acp extraTabs');
    }

    function getPsCode()
    {
        return $this->pscode;
    }

    function handleSuccess($bp)
    {
        $bp['reportCode'] = 'success';
        return $this->handleAsPage($bp);
    }

    function handleFailure($bp)
    {
        $bp['reportCode'] = 'failure';
        return $this->handleAsPage($bp);
    }

    function handleAsPage($bp)
    {
        $bp = $this->extractBParams($bp);
        $this->tpl();
        $this->tpl->define(array(
            'reportBody' => 'shop/order/payment/report.tpl'
        ));

        $this->tpl->assign(array(
            'REPORT_STATUS' => '',
            'ORDER_CODE' => '',
            'ORDER_PAYSYS_CODE' => '',
            'REPORT_TITLE' => '',
            'REPORT_MESSAGE' => '',
            'REPORT_PAYSYS_SPECIFIC' => '',
        ));
        $_tb = \Verba\_oh('content');
        try {

            $modOrder = \Verba\_mod('order');
            $order = $modOrder->getOrderByCode($bp['iid']);

            if (!$order instanceof \Mod\Order\Model\Order) {
                throw new \Exception(\Lang::get('order not_found'));
            }

            $supportEmail = $modOrder->gC('mailing to support');
            if (!$supportEmail) {
                $supportEmail = 'admin@' . SYS_PRIMARY_HOST;
            }
            $pscode = $order->getPaysys()->getCode();
            $statusUrl = new \Url($modOrder->gC('url status'));
            $statusUrl->setParams(array('iid' => $order->code));
            $this->tpl->assign(array(
                'REPORT_STATUS' => $bp['reportCode'],
                'REPORT_TITLE' => '',
                'ORDER_CODE' => htmlspecialchars($order->code),
                'ORDER_PAYSYS_CODE' => $pscode,
                'ORDER_STATUS_PAGE_URL' => $statusUrl->get(true),
            ));

            $qm = new \Verba\QueryMaker($_tb, false, true);
            $qm->addWhere(1, 'active');
            $qm->addWhereIids(array('pay-' . $bp['reportCode'], 'pay-' . $bp['reportCode'] . '-' . $pscode));
            $q = $qm->getQuery();
            $sqlr = $this->DB()->query($q);

            if ($sqlr && $sqlr->getNumRows()) {
                $msgs = array();
                while ($row = $sqlr->fetchRow()) {
                    $row['text'] = $this->tpl->parse_template($row['text']);
                    $msgs[$row[$_tb->getStringPAC()]] = $row;
                }

                if (isset($msgs['pay-' . $bp['reportCode']])) {
                    $title = $msgs['pay-' . $bp['reportCode']]['title'];
                    $this->tpl->assign(array(
                        'REPORT_MESSAGE' => $msgs['pay-' . $bp['reportCode']]['text'],
                    ));
                }
                if (isset($msgs['pay-' . $bp['reportCode'] . '-' . $pscode])) {
                    $this->tpl->assign(array(
                        'REPORT_PAYSYS_SPECIFIC' => $msgs['pay-' . $bp['reportCode'] . '-' . $pscode]['text'],
                    ));
                }
            }

            if (!isset($title)) {
                $title = \Verba\Lang::get('order general' . ucfirst($bp['reportCode']));
            }
            $this->tpl->assign(array(
                'REPORT_TITLE' => $title,
            ));

            return $this->tpl->parse(false, 'reportBody');

        } catch (\Exception $e) {
            $row = $_tb->getData('pay-error', 1);
            $msg = $e->getMessage();

            if (is_array($row)) {
                $row['text'] = $this->tpl->parse_template($row['text']);
                $msg .= $row['text'];
            }

            $this->tpl->assign(array(
                'REPORT_STATUS' => 'error',
                'REPORT_TITLE' => \Verba\Lang::get('order generalError'),
                'REPORT_MESSAGE' => $msg,
            ));

            $this->log()->error($e->getMessage());
            return $this->tpl->parse(false, 'reportBody');

        }
    }

    function getSpecificContentSuccess()
    {
        return '';
    }

    function getSpecificContentFailure()
    {
        return '';
    }

    function loadTrans($orderId)
    {

        $qm = new \Verba\QueryMaker($this->_tx);

        $qm->addWhere($orderId, 'orderId');
        $qm->addOrder(array('id' => 'd'));

        $sqlr = $qm->run();

        $trans = array();
        if (!$sqlr || !$sqlr->getNumRows()) {
            return $trans;
        }

        while ($row = $sqlr->fetchRow()) {
            $trans[$row['id']] = $this->_tx->initItem($row);
        }
        return $trans;
    }

    function extractOrderDataFromRequest(&$ct)
    {
        return;
    }

    function extractOrderIdFromEnv()
    {
        return null;
    }

    function convertOurCurCodeToIntCurCode($ourCurCode)
    {
        $curIntCodes = $this->gC('curIntCode');

        if (is_array($curIntCodes)
            && !empty($curIntCodes)
            && array_key_exists($ourCurCode, $curIntCodes)) {

            return $curIntCodes[$ourCurCode];

        }

        return $ourCurCode;
    }

    function convertIntCurCodeToOurCurCode($intCurCode)
    {
        $curIntCodes = $this->gC('curIntCode');

        if (is_array($curIntCodes)
            && !empty($curIntCodes)
            && count(($keys = array_keys($curIntCodes, $intCurCode)))
        ) {
            return current($keys);
        }

        return $intCurCode;
    }

    /**
     * @param $value string|integer
     * @return string|integer|bool
     */
    function validateAccountValue($value, $curId = false)
    {
        $value = trim($value);
        return $value;
    }

    function updateOrderStatus($n)
    {
        if (!$n instanceof Notify
            || !$n->Order
            || !$n->Order->getId()
        ) {
            return false;
        }
        try {
            $_order = \Verba\_oh('order');
            $mOrder = \Verba\_mod('order');
            $valid_payment_statuses = $mOrder->gC('paymentStatusAliases');
            $ae = $_order->initAddEdit('edit');
            $ae->setIID($n->Order->getId());

            $data = array();

            if (array_key_exists($n->status, $valid_payment_statuses)) {
                $data['status'] = $valid_payment_statuses[$n->status];
            }
            $data['statusMsg'] = $n->getDescription();

            if ($n->successPayment()) {
                /**
                 * @var $buyerAcc \Mod\Account\Model\Account
                 */
                $buyerAcc = $n->Order->Buyer()->Accounts()->getAccountByCur($n->Order->getCurrency());

                $balopCause = new \Mod\Balop\Cause\PaymentSuccess($n->getTx());
                $balop = $buyerAcc->balanceUpdate($balopCause);

                $data['payed'] = 1;

                $ae->addExtendedData(array(
                    '__OrderPaymentCause' => $balop
                ));
            }

            $ae->setGettedObjectData($data);

            $ae->addedit_object();
            return $ae;
        } catch (\Exception $e) {
            $this->log()->error($e->getMessage());
            return false;
        }
    }
}
