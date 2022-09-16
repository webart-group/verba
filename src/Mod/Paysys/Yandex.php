<?php

namespace Verba\Mod\Paysys;

use Verba\Mod\Instance;

class Yandex extends \Verba\Mod
{
    use \Verba\ModInstance;
    use \Verba\Mod\Payment\Paysys;

    function extractOrderIdFromEnv()
    {
        if (isset($_REQUEST['label'])) {
            return $_REQUEST['label'];
        }
        return null;
    }

    function createAutoSendForm($orderId)
    {
        $payRq = new PaySend_Yandex($orderId);
        $order = \Verba\_mod('order')->getOrderByCode($orderId);
        $topay = $order->getTopay();
        $this->tpl->define(array(
            'rqFrom' => 'shop/paysys/rq_form.tpl',
        ));

        $fs = '';
        foreach ($payRq->genRequestData() as $k => $v) {
            $fs .= "\n" . '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
        }

        $this->tpl->assign(array(
            'RQ_METHOD' => 'POST',
            'RQ_FIELDS' => $fs,
            'RQ_URL' => $payRq->url,
            'RQ_SUBMIT_BUTTON' => \Verba\Lang::get('yandex paySubmitButton', array('topay' => $topay, 'unit' => $currShort)),
        ));
        return $this->tpl->parse(false, 'rqFrom');
    }

    function handleNotify($bp = null)
    {
        $bp = $this->extractBParams($bp);

        //$c = var_export($_REQUEST, true);
//    file_put_contents(SYS_VAR_DIR.'/yad_log.txt', $c);
//    $this->log()->event($c);

        try {
            if (!$bp['iid']) {
                throw new Exception('Unknown Order Id');
            }
            $n = new PayNotify_Yandex($bp['iid']);

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

            $this->updateOrderStatus($n);

        } catch (Exception $e) {
            $this->log()->error($e->getMessage() . "\n\nRequest:\n" . var_export($_REQUEST, true));
            return '';
        }

        return '';
    }

    function updateOrderStatus($n)
    {
        if (!$n instanceof PayNotify_Yandex
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

    function parsePaymentStatus($order)
    {
        //require_once(SYS_VIEWS_DIR.'/shop/paysys/yandex/status/paymentstatus.php');
        $vw = new ViewOrderYandexStatus();
        return $vw->parse($order);
    }

    function extractOrderDataFromRequest(&$ct)
    {
        if (!is_array($ct)) {
            return false;
        }
        if (isset($ct['label'])) {
            $ct['iid'] = $ct['label'];
            $ct['__orderDataFoundBy'] = 'yandex';
        }
        return;
    }

    /**
     * @param $value string|integer
     * @return string|integer|bool
     */
    function validateAccountValue($value, $curId = false)
    {
        $value = trim($value);
        if (!preg_match("/^41001\d{9}$/", $value)) {
            return false;
        }
        return $value;
    }
}
