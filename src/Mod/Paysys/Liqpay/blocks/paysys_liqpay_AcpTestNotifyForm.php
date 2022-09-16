<?php

class paysys_liqpay_AcpTestNotifyForm extends \Verba\Block\Html
{
    public $templates = array(
        'content' => '/shop/paysys/liqpay/acp/testnotifyform.tpl'
    );

    function build()
    {
        $mod = \Verba\_mod('paysys_liqpay');
        if (isset($_SESSION['paysys_liqpay_testnotify']) && is_array($_SESSION['paysys_liqpay_testnotify'])) {
            $s = &$_SESSION['paysys_liqpay_testnotify'];
        } else {
            $s = array();
        }
        $data = array(
            'version' => isset($s['version']) && $s['version'] ? $s['version'] : $mod->gc('version'),
            'public_key' => isset($s['public_key']) && $s['public_key'] ? $s['public_key'] : '',
            'amount' => isset($s['amount']) && $s['amount'] ? $s['amount'] : 0,
            'currency' => isset($s['currency']) && $s['currency'] ? $s['currency'] : '',
            'description' => isset($s['description']) && $s['description'] ? $s['description'] : '',
            'order_id' => isset($s['order_id']) && $s['order_id'] ? $s['order_id'] : '',
            'type' => isset($s['type']) && $s['type'] ? $s['type'] : '',
            'transaction_id' => isset($s['transaction_id']) && $s['transaction_id'] ? $s['transaction_id'] : '',
            'sender_phone' => isset($s['sender_phone']) && $s['sender_phone'] ? $s['sender_phone'] : '',
            'status' => isset($s['status']) && $s['status'] ? $s['status'] : 'success',
        );

        $data['signature'] = \PaySignature_Liqpay::genSignature($mod->gc('signature'), \PaySignature_Liqpay::makeHashString($s));

        $this->tpl->assign(array(
            'INST_RANDOM' => \Verba\Hive::make_random_string(5, 5),
            'VAL_VERSION' => $data['version'],
            'VAL_PUBLIC_KEY' => $data['public_key'],
            'VAL_AMOUNT' => $data['amount'],
            'VAL_CURRENCY' => $data['currency'],
            'VAL_DESCRIPTION' => $data['description'],
            'VAL_ORDER_ID' => $data['order_id'],
            'VAL_TYPE' => $data['type'],
            'VAL_TRANSACTION_ID' => $data['transaction_id'],
            'VAL_SENDER_PHONE' => $data['sender_phone'],
            'VAL_STATUS' => $data['status'],
            'VAL_SIGNATURE' => $data['signature'],
        ));

        $this->content = $this->tpl->parse(false, 'content');
        return $this->content;
    }
}
