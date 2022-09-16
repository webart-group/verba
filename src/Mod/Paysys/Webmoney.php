<?php

namespace Verba\Mod\Paysys;

use \Verba\Mod\Instance;
use \Verba\Mod\Payment\Paysys;

class Webmoney extends \Verba\Mod
{

    use \Verba\ModInstance;
    use Paysys;

    protected $_tx_code = 'tx_webmoney';

    function extractOrderIdFromEnv($ct = false)
    {
        if (!is_array($ct)) {
            $ct = $_REQUEST;
        }

        if (array_key_exists('orderCode', $ct)) {
            return $ct['orderCode'];
        }

        return null;
    }

    function extractOrderDataFromRequest(&$ct)
    {
        if (!is_array($ct)) {
            return false;
        }
        if (isset($ct['orderCode'])) {
            $ct['iid'] = $ct['orderCode'];
            $ct['__orderDataFoundBy'] = 'webmoney';
        }
        return;
    }

    function getWalletLetterByCurrencyId($curId)
    {
        switch ($curId) {
            case 40: //usd
                return 'Z';
            case 45: // rur
                return 'R';
            case 37: //uah
                return 'U';
            case 54: // eur
                return 'E';
        }
        return false;
    }

    /**
     * @param $value string|integer
     * @return string|integer|bool
     */
    function validateAccountValue($value, $curId = false)
    {
        $value = trim($value);
        $l = $this->getWalletLetterByCurrencyId($curId);
        if (!preg_match('/^' . $l . '\d{12}$/', $value)) {
            return false;
        }
        return $value;
    }

    function getMerchantPurse($curCode)
    {
        return $this->_c['purses'][$curCode];
    }

}

Webmoney::$_config_default = array(
    'haveGataway' => 1,
    'paysyscode' => 'webmoney',
    'sysCurrencyId' => 39,
    'paymentUrl' => 'https://merchant.webmoney.ru/lmi/payment_utf.asp',
    'pass' => 'f292dabb41f1cee7a38ef08dcea9bcc0',
    'successUrl' => '/order/success/webmoney',
    'failureUrl' => '/order/failure/webmoney',
    'notifyUrl' => '/order/notify/webmoney',
    'statusUrl' => '/order/status',
    'payLogTable' => 'paysys_webmoney_payrq',
    'notifyLogTable' => 'paysys_webmoney_notify',
    'simMode' => null,
    'trustedIP' => array(
        '212.118.48', '212.158.173', '91.200.28', '91.227.52'
    ),
    'purses' => array(
        'RUR' => 'R872857781921',
        'EUR' => 'E587866340364',
        'USD' => 'Z164537194304',
    ),
    'acp' =>
        array(
            'extendTabs' =>
                array(
                    'UcpProps' =>
                        array(
                            'class' => 'CustomizeModConfig',
                            'url' => '/acp/h/paysys_webmoney/',
                        ),
                ),
        ),
    '_customizable' =>
        array(
            'actionUrl' => '/acp/h/paysys_webmoney/customizecfg',
            'keys' =>
                array(
                    'gatewayUrl' =>
                        array(
                            'title' => 'URL шлюза',
                            'datatype' => 'text',
                            'econfig' =>
                                array(
                                    'classes' => 'ucp-gateway-url',
                                ),
                        ),
                    'merchantId' =>
                        array(
                            'title' => 'MerchantID',
                            'datatype' => 'string',
                        ),
                    'successUrl' =>
                        array(
                            'title' => 'Результат успешной транзакции',
                            'datatype' => 'string',
                        ),
                    'failureUrl' =>
                        array(
                            'title' => 'Результат не прошедшей транзакции',
                            'datatype' => 'string',
                        ),
                    'sysCurrencyId' =>
                        array(
                            'title' => 'ID Валюты используемой в расчетах с Webmoney',
                            'datatype' => 'integer',
                        ),
                ),
        ),
);
