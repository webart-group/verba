<?php

class paysys_liqpay_AcpTestNotifyHandler extends \Verba\Block\Html
{

    function build()
    {

        $data = $_REQUEST['data'];
        $signature = isset($_REQUEST['signature'])
        && is_string($_REQUEST['signature'])
        && !empty($_REQUEST['signature'])
            ? $_REQUEST['signature']
            : false;
        $mod = \Verba\_mod('paysys_liqpay');
        $hashedData = \PaySignature_Liqpay::makeHashString($data);
        $orderId = isset($data['order_id']) ? (string)$data['order_id'] : '';

        $_SESSION['paysys_liqpay_testnotify'] = array(
            'version' => isset($data['version']) ? (string)$data['version'] : '',
            'public_key' => isset($data['public_key']) ? (string)$data['public_key'] : '',
            'amount' => isset($data['amount']) ? (string)$data['amount'] : 0,
            'currency' => isset($data['currency']) ? (string)$data['currency'] : '',
            'description' => isset($data['description']) ? $data['description'] : '',
            'order_id' => $orderId,
            'type' => isset($data['type']) ? (string)$data['type'] : '',
            'transaction_id' => isset($data['transaction_id']) ? (string)$data['transaction_id'] : '',
            'sender_phone' => isset($data['sender_phone']) ? (string)$data['sender_phone'] : '',
            'status' => isset($data['status']) ? (string)$data['status'] : 'success',
        );

        if (!$signature) {
            $signature = \PaySignature_Liqpay::genSignature($mod->gC('signature'), $hashedData);
        }

        $notifyUrl = new \Url($mod->gC('notifyUrl'));

        $this->content = array(
            'data' => $hashedData,
            'signature' => $signature,
            'order_id' => $orderId,
            'url' => $notifyUrl->get(true),
        );
        return $this->content;
    }

}
