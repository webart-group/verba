<?php

namespace Verba\Mod\Paysys;

use Verba\Mod\Instance;
use Verba\Mod\Paysys\Unitpay\Transaction\CreateBill;
use Verba\Mod\Paysys\Unitpay\Transaction\Send;
use Verba\Mod\Paysys\Unitpay\Transaction\Notify as TransactionNontify;

class Unitpay extends \Verba\Mod
{
    use \Verba\ModInstance;
    use \Verba\Mod\Payment\Paysys;

    protected $_tx_code = 'tx_unitpay';

    const NOTIFY_HANDLER = TransactionNontify::class;

    function extractOrderIdFromEnv()
    {
        if (isset($_REQUEST['params']['account'])) {
            return trim($_REQUEST['params']['account']);
        }
        return null;
    }

    function extractOrderDataFromRequest(&$ct)
    {
        if (!is_array($ct)) {
            return false;
        }
        if (isset($ct['params']['account'])) {
            $ct['iid'] = trim($ct['params']['account']);
            $ct['__orderDataFoundBy'] = 'unitpay';
        }
        return;
    }

    static function  getNotifyHandler()
    {
        return self::NOTIFY_HANDLER;
    }
}
