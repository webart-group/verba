<?php

namespace Verba\Act\MakeList\Handler;

use ObjectType\Attribute\Handler as AttributeHandler;

class Field extends AttributeHandler implements HandlerInterface
{

    /**
     * @var \Verba\Act\MakeList
     */
    public $list;

    public $sharedTpl;

    function __construct($oh, $A, $cfg = false, $ah = null)
    {
        parent::__construct($oh, $A, $cfg, $ah);
        $this->list = $this->ah;
    }

    function init()
    {
        if (!$this->attr_code && $this->list->fieldCode) {
            $this->attr_code = $this->list->fieldCode;
        }

        parent::init();
    }

    function initTpl()
    {
        if ($this->sharedTpl) {
            $this->tpl = \FastTemplate::getShared(get_class($this), [
                'templates' => $this->templates
            ]);
        }else{
            $this->tpl = \Verba\Hive::initTpl();
            $this->tpl->define($this->templates);
        }
    }
}
