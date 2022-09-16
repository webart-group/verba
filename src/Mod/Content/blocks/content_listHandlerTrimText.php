<?php

namespace Verba\Mod\Content\Act\MakeList\Handler\Field;

class Trim extends \Act\MakeList\Handler
{
    public $field;
    public $length = 300;

    function run()
    {
        if (!is_string($this->field) || !isset($this->list->row[$this->field])) {
            return '';
        }
        return \HTMLGetFormattedText($this->list->row[$this->field], $this->length);
    }
}
