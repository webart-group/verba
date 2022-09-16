<?php

namespace Verba\Mod\Paysys;

use Verba\Mod\Instance;

class Qiwi extends \Verba\Mod
{

    use \Verba\ModInstance;
    use \Verba\Mod\Payment\Paysys;

    public $soapNotifyParams;

    function extractOrderIdFromEnv()
    {
        if (isset($_REQUEST['txn_id'])) {
            return $_REQUEST['txn_id'];
        } elseif (isset($_REQUEST['order'])) {
            return $_REQUEST['order'];
        }
        return null;
    }

    function extractID($bp)
    {
        $iid = parent::extractID($bp);
        if (!$iid && isset($_REQUEST['bill_id'])) {
            $iid = $_REQUEST['bill_id'];
        }
        return $iid;
    }

    function getProtocol($protoalias = null)
    {
        if ($this->protocol !== null) {
            return $this->protocol;
        }
        if (!$protoalias) {
            $protoalias = $this->gC('protocol');
        }
        $protoClass = $this->gC('protocols ' . $protoalias . ' class');
        if (!class_exists($protoClass)) {
            $protoClass = 'PaymentProtocol';
        }
        $dcfg = $this->gC('protocols default');
        $pcfg = $this->gC('protocols ' . $protoalias);
        $cfg = array();
        if (is_array($dcfg)) {
            $cfg = $dcfg;
        }
        if (is_array($pcfg)) {
            $cfg = array_replace_recursive($cfg, $pcfg);
        }

        $this->protocol = new $protoClass($cfg, $this);
        return $this->protocol;
    }

    function createAutoSendForm($orderId)
    {
        $proto = $this->getProtocol();

        $payRq = $proto->createPaysend($orderId);

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
            'RQ_METHOD' => $proto->cfg['redirectMethod'],
            'RQ_FIELDS' => $fs,
            'RQ_URL' => $payRq->url,
            'RQ_SUBMIT_BUTTON' => \Verba\Lang::get('qiwi paySubmitButton', array('topay' => $topay, 'unit' => ''/*$currShort*/)),
        ));
        return $this->tpl->parse(false, 'rqFrom');
    }

    function handleNotify($bp = null)
    {

//    $c = var_export($_REQUEST, true)."\n\n".var_export($_SERVER, true);
//    file_put_contents(SYS_VAR_DIR.'/qiw_log.txt', $c);

        $protoName = $this->gC('protocol');
        if ($protoName == 'soap') {
            return $this->handleNotifySoap($bp);
        } elseif ($protoName == 'visa') {
            return $this->handleNotifyVisa($bp);
        }
    }

    protected function handleNotifyVisa($bp = null)
    {
        $bp = $this->extractBParams($bp);
        $proto = $this->getProtocol();
        try {
            $n = $proto->getNotify($bp['iid']);
            $updOrder = false;
            $response = 0;
            switch ($n->status) {
                case 'success':
                    $this->log()->event("Payment is successful. Order:" . $n->orderCode);
                    $updOrder = true;
                    break;
                case 'error':
                    $this->log()->error("Payment Notify error. Status: " . $n->status . ", Order:" . $n->orderCode);
                    $updOrder = true;
                    break;
                case 'not_valid':
                    $this->log()->error("Payment Result not valid. Status: " . $n->status . ", Order:" . $n->orderCode);
                    $updOrder = true;
                    break;
                case 'wait':
                    break;
                default:
                    $this->log()->error("Notify response unknown status" . var_export($n, true));
                    break;
            }
            $n->updateTransactionByNotify();
            if ($updOrder) {
                $this->updateOrderStatus($n);
            }
        } catch (\Exception $e) {
            $this->log()->error($e->getMessage());
        }

        header('Content-Type: text/xml');
        return '<?xml version="1.0"?><result><result_code>' . $response . '</result_code></result>';
    }

    function updateBill($param)
    {
        $this->soapNotifyParams = $param;
    }

    function updateOrderStatus($n)
    {
        if (!$n instanceof Qiwi\Transaction
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
        } catch (\Exception $e) {
            $this->log()->error($e->getMessage());
            return false;
        }
    }

    function extractOrderDataFromRequest(&$ct)
    {
        if (!is_array($ct)) {
            return false;
        }
        if (isset($ct['order'])) {
            $ct['iid'] = $ct['order'];
            $ct['__orderDataFoundBy'] = 'qiwi';
        }
        return;
    }

    /**
     * @param $value string|integer
     * @return string|integer|bool
     */
    function validateAccountValue($value, $curId = false)
    {
        $value = preg_replace("/[\D]/", '', $value);
        if (!preg_match("/[0-9]{11,12}/i", $value)) {
            return false;
        }
        return $value;
    }
}
