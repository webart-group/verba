<?php

namespace Verba\Mod\Paysys;

use Verba\Mod\Instance;

class Liqpay extends \Verba\Mod
{

    use \Verba\ModInstance;
    use \Verba\Mod\Payment\Paysys;

//  function createAutoSendForm($orderId){
//    $prq = new PaySend_Liqpay($orderId);
//    $order = \Verba\_mod('order')->getOrder($orderId, true);
//    $topay = $order->getTopay();
//    $currShort = $order->currency['short'];
//    $this->tpl->define(array(
//      'rqFrom' => 'shop/paysys/rq_form.tpl',
//      'rqFromFields' => 'shop/paysys/liqpay/rq_form_fields.tpl',
//    ));
//
//    $this->tpl->assign(array(
//      'RQ_METHOD' => 'POST',
//      'OPERATION_XML' =>  base64_encode($prq->requestData),
//      'OPERATION_SIGN' => $prq->signature,
//      'RQ_URL' => $prq->url,
//      'RQ_SUBMIT_BUTTON' => \Verba\Lang::get('liqpay paySubmitButton', array('topay' => $topay, 'unit' => $currShort)),
//    ));
//    $this->tpl->parse('RQ_FIELDS', 'rqFromFields');
//    $prq->logRq();
//    return $this->tpl->parse(false, 'rqFrom');
//  }

    function handleSuccess($bp)
    {
        $bp['reportBody'] = 'shop/paysys/liqpay/success.tpl';
        $bp['reportTitleMsg'] = \Verba\Lang::get('order generalSuccess');
        return $this->handleAsPage($bp);
    }

    function handleFailure($bp)
    {
        $bp['reportBody'] = 'shop/paysys/liqpay/failure.tpl';
        $bp['reportTitleMsg'] = \Verba\Lang::get('order failureError');
        return $this->handleAsPage($bp);
    }

    function handleAsPage($bp)
    {
        $bp = $this->extractBParams($bp);
        $this->tpl->define(array(
            'reportBody' => $bp['reportBody']
        ));
        $tid = $_REQUEST['tlid'];
        $order = \Verba\_mod('order')->getOrderByCode($bp['iid']);
        $modOrder = \Verba\_mod('order');
        $supportEmail = $modOrder->gC('mailing to support');
        if (!$supportEmail) {
            $supportEmail = 'admin@' . SYS_PRIMARY_HOST;
        }
        $this->tpl->assign(array(
            'ORDER_CODE' => htmlspecialchars($bp['iid']),
            'ORDER_SUPPORT_EMAIL' => $supportEmail
        ));
        if (!$order instanceof \Verba\Mod\Order\Model\Order) {
            $this->tpl->define(array(
                'reportBody' => 'shop/paysys/liqpay/error.tpl'
            ));
            $this->tpl->assign(array(
                'REPORT_TITLE' => \Verba\Lang::get('order generalError'),
                'REPORT_MSG' => \Verba\Lang::get('order not_found'),
            ));
            $this->log()->error(\Lang::get('order not_found'));
            return $this->tpl->parse(false, 'reportBody');

        } elseif (!$order->getTran($tid)) {
            $this->tpl->define(array(
                'reportBody' => 'shop/paysys/liqpay/error.tpl'
            ));
            $this->tpl->assign(array(
                'REPORT_TITLE' => \Verba\Lang::get('order generalError'),
                'REPORT_MSG' => \Verba\Lang::get('order transaction_not_found'),
            ));
            $this->log()->error(\Lang::get('order transaction_not_found'));
            return $this->tpl->parse(false, 'reportBody');
        }

        $tran = $order->getTran($tid);
        $this->tpl->assign(array(
            'REPORT_TITLE' => isset($bp['reportTitleMsg']) ? $bp['reportTitleMsg'] : '',
            'REPORT_MSG' => $tran->code,
            'BACK_TO_SHOP_URL' => \Verba\Hive::getBackURL(),
        ));

        return $this->tpl->parse(false, 'reportBody');
    }

    function updateOrderStatus($n)
    {
        if (!$n instanceof Liqpay\Transaction\Notify
            || !$n->orderId
        ) {
            return false;
        }
        try {
            $_order = \Verba\_oh('order');
            $mOrder = \Verba\_mod('order');
            $cfg = $mOrder->gC('paymentStatusAliases');
            $ae = $_order->initAddEdit('edit');
            $ae->setIID($n->orderId);
            $data = array();

            if ($n->isValid()) {
                $statusCode = $n->convertStatus();
                switch ($statusCode) {
                    case 'success':
                        $data['status'] = $cfg['success'];
                        break;
                    case 'wait':
                        $data['status'] = $cfg['wait'];
                        break;
                    case 'error':
                        $data['status'] = $cfg['error'];
                        break;
                }
            }
            if (!isset($data['status'])) {
                $data['status'] = $cfg['not_valid'];
            }

            $data['statusMsg'] = $n->getStatusMsg();

            $ae->setGettedObjectData($data);
            $r = $ae->addedit_object();
            return $ae;
        } catch (\Exception $e) {
            $this->log()->error($e->getMessage());
            return false;
        }
    }

    function testNotify($bp)
    {
        $bp = $this->extractBParams($bp);
        $q = "SELECT *, DATE_FORMAT(`purchaseTime`, '%y%m%d%H%i%s') as fmted FROM " . SYS_DATABASE . ".`" . $this->gC('transTable') . "` WHERE "
            . "`orderId` = '" . $bp['iid'] . "'"
            . " ORDER BY id desc"
            . " LIMIT 1";
        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getNumRows()) {
            $this->log->error('Payment Request Data not recived');
            $row = array();
        } else {
            $row = $sqlr->fetchRow();
        }

        $f = array(
            'TranCode' => '000',
            'XID' => '',
            'Rrn' => '',
            'ProxyPan' => '',
            'ApprovalCode' => '',
            'OrderID' => $row['orderId'],
            'PurchaseTime' => $row['fmted'],
            'TotalAmount' => $row['totalAmount'],
            'Currency' => $row['currency'],
            'MerchantID' => $row['merchantId'],
            'TerminalID' => $row['terminalId'],
            'Signature' => '',
            'SD' => $row['sd'],
        );
        $this->tpl->define(array(
            'tfinput' => 'shop/paysys/liqpay/test-notify/input.tpl',
            'trqBody' => 'shop/paysys/liqpay/test-notify/form.tpl',
        ));
        foreach ($f as $fname => $fv) {
            $this->tpl->assign(array(
                'TF_NAME' => $fname,
                'TF_VALUE' => $fv,
            ));
            $this->tpl->parse('TEST_RQ_DATA', 'tfinput', true);
        }
        return \Verba\Response\Json::wrap(true, $this->tpl->parse(false, 'trqBody'));
    }

    function loadTrans($orderId)
    {
        $this->DB();
        $q = "SELECT * FROM `" . SYS_DATABASE . "`.`" . $this->gC('transTable') . "` WHERE `orderId` = '" . $orderId . "'";
        $sqlr = $this->DB->query($q);
        $trns = array();
        if (!$sqlr || !$sqlr->getNumRows()) {
            //return $trans;
            return null;
        }
        while ($row = $sqlr->fetchRow()) {
            $trns[$row['id']] = new \Verba\Mod\Order\Transaction($row);
        }
        $whereIids = $this->DB->makeWhereStatement(array_keys($trns), 'rqId');
        $q = "SELECT * FROM `" . SYS_DATABASE . "`.`" . $this->gC('transLogTable') . "` WHERE " . $whereIids . " ORDER BY `rqId` DESC, `created` DESC";
        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getNumRows()) {
            $tl = array();
            foreach ($trns as $trn) {
                $trn->setTlog($tl);
            }
        } else {
            while ($row = $sqlr->fetchRow()) {
                $trns[$row['rqId']]->setTlog($row);
            }
        }
        return $trns;
    }

    function parsePaymentStatus($order)
    {
        //require_once(SYS_VIEWS_DIR.'/shop/paysys/liqpay/status/paymentstatus.php');
        $vw = new \ViewOrderLiqpayStatus();
        return $vw->parse($order);
    }

}
