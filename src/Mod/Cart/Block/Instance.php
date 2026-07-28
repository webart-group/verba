<?php

namespace Verba\Mod\Cart\Block;

class Instance extends \Verba\Block\Json
{

    function build()
    {

        $this->setScripts([
            ['cart customer', 'shop'],
        ]);

        /**
         * @var \Verba\Mod\Cart $Cart
         */

        $Cart = \Verba\_mod('cart');
        //$paysys = $Cart->getPaysys();
        //$currency = $Cart->getCurrency();

        $cfg = $Cart->packToCfg();

        $this->addJsBefore("
window.CartInstance = new Cart(".json_encode($cfg).");
window.CartInstance.init();");

        return '';
    }

}
