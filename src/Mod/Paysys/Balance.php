<?php

namespace Mod\Paysys;

use \Mod\Instance;
use \Mod\Payment\Paysys;

class Balance extends \Verba\Mod
{

    use \Verba\ModInstance;
    use \Mod\Payment\Paysys;

    public $pscode = 'balance';

}

Balance::$_config_default = array(
    'haveGataway' => true,
);
