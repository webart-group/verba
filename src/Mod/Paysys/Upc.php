<?php

namespace Mod\Paysys;

use Mod\Instance;

class Upc extends \Verba\Mod
{
    use \Verba\ModInstance;
    use \Mod\Payment\Paysys;

    function createAutoSendForm($orderId)
    {
        $prq = new PaySend_UPC($orderId);

        $this->tpl->define(array(
            'rqFrom' => 'shop/paysys/rq_form.tpl'
        ));
        $fields = array();
        $fields['OrderID'] = $prq->orderCode;
        $fields['PurchaseTime'] = $prq->purchaseTime;
        $fields['TotalAmount'] = $prq->totalAmount;
        $fields['Currency'] = $prq->currency;
        if ($prq->altTotalAmount) {
            $fields['AltTotalAmount'] = $prq->altTotalAmount;
            $fields['AltCurrency'] = $prq->altCurrency;
        }
        $fields['locale'] = $prq->locale;
        $fields['Version'] = $prq->version;
        $fields['MerchantID'] = $prq->merchantId;
        $fields['TerminalID'] = $prq->terminalId;
        $fields['PurchaseDesc'] = htmlspecialchars($prq->purchaseDesc);
        $fields['Signature'] = $prq->base64sign;
        $fields['SD'] = $prq->sessionId;

        $fs = '';
        foreach ($fields as $k => $v) {
            $fs .= "\n" . '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
        }

        $this->tpl->assign(array(
            'RQ_METHOD' => 'POST',
            'RQ_FIELDS' => $fs,
            'RQ_URL' => $prq->url,
            'RQ_SUBMIT_BUTTON' => \Verba\Lang::get('paysys upc paySubmitButton'),
        ));
        $prq->logRq();
        return $this->tpl->parse(false, 'rqFrom');
    }

    function handleNotify($bp)
    {
        $orderId = $_REQUEST['OrderID'];
        $n = new PayNotify_UPC($orderId);
        $this->tpl();
        if (!$n->isValid()) {
            $n->state = 'reverse';
            $n->setReason($n->getErrorsAsReason());
        } else {
            $n->state = 'approve';
            $n->setReason($this->getMsgByTranCode($n->tranCode));
            $this->updatePayRq($n);
        }

        if ($n->isValid() && $n->tranCode == '000') {
            $fUrl = new \Url($this->gC('successUrl'));
        } else {
            $fUrl = new \Url($this->gC('failureUrl'));
        }
        $this->updateOrderStatus($n);

        $fUrl->setParams(array('iid' => (string)$n->orderCode, 'tlid' => (string)$n->transData['id']));

        $this->tpl->define(array(
            'rspBody' => 'shop/paysys/upc/notifyResponse.tpl'
        ));
        $this->tpl->assign(array(
            'NT_MERCHANTID' => $n->merchantId,
            'NT_TERMINALID' => $n->terminalId,
            'NT_ORDERID' => $n->orderCode,
            'NT_CURRENCY' => $n->currency,
            'NT_TOTALAMOUNT' => $n->totalAmount,
            'NT_XID' => $n->xid,
            'NT_PURCHASE_TIME' => $n->purchaseTime,
            'NT_ACTION' => $n->state,
            'NT_REASON' => $n->reason,
            'NT_FORWARD_URL' => $fUrl->get(true),
        ));
        return $this->tpl->parse(false, 'rspBody');
    }

    function updateOrderStatus($n)
    {
        if (!$n instanceof PayNotify_UPC
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
            $data['statusMsg'] = \Verba\Lang::get('paysys upc codes ' . $n->tranCode);
            if (!$data['statusMsg']) {
                $data['statusMsg'] = '';
            }
            if ($n->isValid() && $n->tranCode == '000') {
                $data['status'] = $cfg['success'];
            } else {
                $data['status'] = $cfg['error'];
            }

            $ae->setGettedObjectData($data);
            $r = $ae->addedit_object();
            return $ae;
        } catch (Exception $e) {
            $this->log()->error($e->getMessage());
            return false;
        }
    }

    function parsePaymentStatus($order)
    {
        //require_once(SYS_VIEWS_DIR.'/shop/paysys/upc/status/paymentstatus.php');
        $vw = new ViewOrderUPCStatus();
        return $vw->parse($order);
    }

    function updatePayRq($n)
    {
        $this->DB();
        $q = "UPDATE `" . SYS_DATABASE . "`.`" . $this->gC('transTable') . "` SET
`xid` = '" . $this->DB->escape_string($n->xid) . "',
`updated` = '" . strftime("%Y-%m-%d %H:%M:%S") . "',
`tranCode` = '" . $this->DB->escape_string($n->tranCode) . "',
`approvalCode` = '" . $this->DB->escape_string($n->approvalCode) . "',
`rrn` = '" . $this->DB->escape_string($n->rrn) . "',
`proxyPan` = '" . $this->DB->escape_string($n->proxyPan) . "',
`reason` = '" . $this->DB->escape_string($n->getReason()) . "'
WHERE
`id` = '" . $this->DB->escape_string($n->transData['id']) . "'";
        $sqlr = $this->DB->query($q);
        if (!$sqlr || !$sqlr->getAffectedRows()) {
            $this->log()->error('Unable to update Transaction rqId[' . var_export($n->transData['id'], true) . '], orderId:[' . var_export($n->orderId, true) . ']');
            return false;
        }
        return $sqlr->getInsertId();
    }

    function loadTrans($orderId)
    {
        $this->DB();
        $q = "SELECT * FROM `" . SYS_DATABASE . "`.`" . $this->gC('transTable') . "` WHERE `orderId` = '" . $orderId . "'";
        $sqlr = $this->DB->query($q);
        $trns = array();
        if (!$sqlr || !$sqlr->getNumRows()) {
            return $trns;
        }
        while ($row = $sqlr->fetchRow()) {
            $trns[$row['id']] = new OrderTransUPC($row);
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

    function getMsgByTranCode($tranCode)
    {
        return \Verba\Lang::get('paysys upc codes ' . $tranCode);
    }

    function extractOrderDataFromRequest(&$ct)
    {
        if (!is_array($ct)) {
            return false;
        }
        if (isset($ct['XID']) && isset($ct['tranCode'])) {
            $ct['iid'] = $ct['OrderID'];
            $ct['__orderDataFoundBy'] = 'upc';
        }
        return;
    }
}
