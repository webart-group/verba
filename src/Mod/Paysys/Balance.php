<?php

namespace Verba\Mod\Paysys;

use \Verba\Mod\Instance;
use \Verba\Mod\Payment\Paysys;

class Balance extends \Verba\Mod
{

    use \Verba\ModInstance;
    use \Verba\Mod\Payment\Paysys;

    public $pscode = 'balance';

}

Balance::$_config_default = array(
    'haveGataway' => true,
);
