<?php

namespace Mod\Cart\Block;

class Instance extends \Verba\Block\Json
{

    function build()
    {
        /**
         * @var \Mod\Cart $Cart
         */

        $Cart = \Verba\_mod('cart');
        //$paysys = $Cart->getPaysys();
        //$currency = $Cart->getCurrency();

        $this->content = $Cart->packToCfg();

        return $this->content;
    }

}
