<?php

namespace Verba\Mod\Paysys;

use Verba\Mod\Instance;

class Robokassa extends \Verba\Mod
{
    use \Verba\ModInstance;
    use \Verba\Mod\Payment\Paysys;

    public $pscode = 'robokassa';

    function extractOrderIdFromEnv()
    {
        if (isset($_REQUEST['shp_orderCode'])) {
            return $_REQUEST['shp_orderCode'];
        }
        return null;
    }

    function createAutoSendForm($orderId)
    {
        $payRq = new PaySend_Robokassa($orderId);
        $order = \Verba\_mod('order')->getOrderByCode($orderId);
        $topay = $order->getTopay();
        $currShort = $order->currency->p('short');
        $this->tpl->define(array(
            'rqFrom' => 'shop/paysys/rq_form.tpl',
            'rqFromFields' => 'shop/paysys/robokassa/rq_form_fields.tpl',
        ));

        $fs = '';
        foreach ($payRq->genRequestData() as $k => $v) {
            $fs .= "\n" . '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
        }

        $this->tpl->assign(array(
            'RQ_METHOD' => 'POST',
            'RQ_FIELDS' => $fs,
            'RQ_URL' => $payRq->url,
            'RQ_SUBMIT_BUTTON' => \Verba\Lang::get('robokassa paySubmitButton', array('topay' => $topay, 'unit' => $currShort)),
        ));
        return $this->tpl->parse(false, 'rqFrom');
    }

    function handleNotify($bp = null)
    {
        $bp = $this->extractBParams($bp);

        try {
            $n = new PayNotify_Robokassa($bp['iid']);

            switch ($n->status) {
                case 'success':
                    $this->log()->event("Payment is successful. Order Id:" . $n->orderId);
                    break;
                case 'error':
                    $this->log()->error("Payment Notify error. Status: " . $n->status . ", Order Id:" . $n->orderId);
                    break;
                case 'not_valid':
                    $this->log()->error("Payment Result not valid. Status: " . $n->status . ", Order Id:" . $n->orderId);
                    break;
                default:
                    $this->log()->error("Notify response unknown status" . var_export($n, true));
                    break;
            }

            if (array_key_exists('status', $n->payTrans)
                && $n->payTrans['status'] != 'success') {
                $n->updateTransactionByNotify();
                $this->updateOrderStatus($n);
            }
        } catch (Exception $e) {
            $this->log()->error($e->getTraceAsString());
        }
        return 'OK' . $n->orderId;
    }

    function updateOrderStatus($n)
    {
        if (!$n instanceof PayNotify_Robokassa
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

            if ($n->isValid()
                && isset($cfg[$n->status])) {
                $data['status'] = $cfg[$n->status];
            }

            if (!isset($data['status'])) {
                $data['status'] = $cfg['not_valid'];
            }

            $data['statusMsg'] = $n->getStatusMsg();

            $ae->setGettedObjectData($data);
            $r = $ae->addedit_object();
            return $ae;
        } catch (Exception $e) {
            $this->log()->error($e->getMessage());
            return false;
        }
    }

    function extractOrderDataFromRequest(&$ct)
    {
        if (!is_array($ct)) {
            return false;
        }
        if (isset($ct['shp_orderCode'])) {
            $ct['iid'] = $ct['shp_orderCode'];
            $ct['__orderDataFoundBy'] = 'robokassa';
        }
        return;
    }

}
