<?php

namespace Mod\Paysys;

use Mod\Instance;

class Cash extends \Verba\Mod
{
    use \Verba\ModInstance;
    use \Mod\Payment\Paysys;

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

