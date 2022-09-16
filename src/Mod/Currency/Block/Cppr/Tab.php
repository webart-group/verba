<?php

namespace Verba\Mod\Currency\Block\Cppr;

class Tab extends \page_eInteractive
{

    /**
     * @var $eid string ID элемента
     */
    public $eid = 'acp_cppr_tool';
    public $component = 'CPPR';
    public $script = 'acp/tools/cppr.js';
    public $style = 'acp/shop/cppr.css';
    public $classes = 'acp-tool-cppr';
    public $group = 'acp-cppr-tool';

    function init()
    {

        $this->tpl->define(array(
            'ui' => '/shop/currency/acp/cppr/tab.tpl'
        ));

    }
}
