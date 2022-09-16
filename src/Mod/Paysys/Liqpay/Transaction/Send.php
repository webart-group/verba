<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 15:18
 */

namespace Verba\Mod\Paysys\Liqpay\Transaction;

class Send extends \Verba\Mod\Paysys\Liqpay\Transaction
{
    public $requestData;

    function __construct($orderSid)
    {
        parent::__construct($orderSid);

        $this->url = $this->mCfg['paymentUrl'];

        $this->totalAmount = $this->orderData->getTopay();
        $this->purchaseTime = date('ymdHis');
        $this->purchaseDesc = \Verba\translit(htmlspecialchars(\Lang::get('order invoiceText', array('invCode' => $this->orderCode))), ' ');

        $this->sessionId = session_id();
        $this->requestData = $this->genRequestData();
        $this->signature = $this->genSignature();

    }

    function genRequestData()
    {

        $mOrder = \Verba\_mod('order');

        $resultUrl = new \Url($mOrder->gC('url status'));
        $resultUrl->setParams(array(
            'iid' => $this->orderCode
        ));
        $resultUrl = $resultUrl->get(true);

        $notifyUrl = new \Url($mOrder->gC('url notify') . '/' . $this->_paysysCode);
        $notifyUrl->setParams(array(
            'iid' => $this->orderCode
        ));
        $notifyUrl = $notifyUrl->get(true);

        $str = "<request>\n"
            . "<version>" . $this->version . "</version>\n"
            . "<merchant_id>" . $this->merchantId . "</merchant_id>\n"
            . "<result_url>" . $resultUrl . "</result_url>\n"
            . "<server_url>" . $notifyUrl . "</server_url>\n"
            . "<order_id>" . $this->orderCode . "</order_id>\n"
            . "<amount>" . $this->totalAmount . "</amount>\n"
            . "<currency>" . $this->currency->p('code') . "</currency>\n"
            . "<description>" . $this->purchaseDesc . "</description>\n"
            . "<default_phone></default_phone>\n"
            . "<pay_way>card</pay_way>\n"
            . "<goods_id></goods_id>\n"
            . "</request>";

        return $str;
    }

    function genSignature()
    {
        $str = $this->mCfg['signature'] . $this->requestData . $this->mCfg['signature'];
        $sign = base64_encode(sha1($str, 1));
        return $sign;
    }

    function logRq()
    {
        $q = "INSERT INTO `" . SYS_DATABASE . "`.`" . \Verba\_mod('paysys_liqpay')->gC('transTable') . "` (
`purchaseTime`,
`orderId`,
`totalAmount`,
`currencyId`,
`description`,
`owner`,
`signature`,
`requestData`,
`merchantId`
) VALUES (
  '" . $this->purchaseTimeToSql($this->purchaseTime) . "',
  '" . $this->orderId . "',
  '" . $this->totalAmount . "',
  '" . $this->currency->getId() . "',
  '" . $this->DB()->escape_string($this->purchaseDesc) . "',
  '" .\Verba\User()->getID() . "',
  '" . $this->signature . "',
  '" . $this->requestData . "',
  '" . $this->merchantId . "'
)";
        $sqlr = $this->DB()->query($q);
        if (!$sqlr) {
            return false;
        }
        return $sqlr->getInsertId();
    }
}
