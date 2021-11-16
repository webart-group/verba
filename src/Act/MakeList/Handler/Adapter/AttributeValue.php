<?php
namespace Verba\Act\MakeList\Handler\Adapter;

use Act\MakeList\Handler\Adapter;

class AttributeValue extends Adapter{

    public $attr_code;

    function initHandler($className, $cfg)
    {
        if(!class_exists($className) || !$this->attr_code) {
            $this->log()->error('Unable to init Handler - bad class name or attr code');
            return false;
        }
        $oh = $this->list->getOh();
        $A = $this->list->getOh()->A($this->attr_code);

        $this->Handler = new $className($oh, $A, $cfg);

        return $this->Handler;
    }
}
