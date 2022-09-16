<?php

namespace Verba\Act\MakeList\Handler;

abstract class Row extends \Verba\Configurable implements HandlerInterface
{

    /**
     * @var \Verba\Act\MakeList
     */
    public $list;

    public $row;

    /**
     * @var \Verba\FastTemplate
     */
    protected $tpl;

    public $templates = [];

    function __construct($list, $cfg)
    {
        $this->list = $list;

        if (is_array($cfg) && count($cfg)) {
            $this->applyConfigDirect($cfg);
        }

        $this->row = $this->list->row;

        $this->init();
    }

    function init()
    {
        $this->initTpl();
    }

    function initTpl() {
        $this->tpl = \Verba\Hive::initTpl();
    }

    function run()
    {
        return null;
    }
}
