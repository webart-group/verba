<?php
namespace Mod\Game\Act\MakeList\Handler\Field;

use \Act\MakeList\Handler\Field;

class Implode extends Field {

    public $fields;
    protected $_fields_array;

    function init()
    {
        if (!is_array($this->fields) && is_string($this->fields)) {
            $this->fields = explode(',', $this->fields);
        }

        if (!count($this->fields)) {
            $this->fields = false;
        }
    }

    function run()
    {

        if (!$this->fields) {
            return '';
        }

        $a = array();

        foreach ($this->fields as $fieldCode) {
            $val = $this->list->rowItem->getValue($fieldCode);
            if ($val) {
                $a[] = $val;
            }
        }

        return implode(', ', $a);
    }
}
