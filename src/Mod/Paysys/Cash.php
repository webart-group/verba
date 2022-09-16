<?php

namespace Verba\Mod\Paysys;

use Verba\Mod\Instance;

class Cash extends \Verba\Mod
{
    use \Verba\ModInstance;
    use \Verba\Mod\Payment\Paysys;

    function parsePaymentStatus($order)
    {
        //require_once(SYS_VIEWS_DIR.'/shop/paysys/cash/status/paymentstatus.php');
        return 'cash';
    }

    function loadTrans()
    {
        return array();
    }
}

