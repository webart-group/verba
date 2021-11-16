<?php

namespace Verba\Act\MakeList\Handler\Field;

use Act\MakeList\Handler\Field;

class MultiPredefined extends Field
{

    function run()
    {

        return $this->list->oh()->ph_multi_predefined_handler($this->attr_code, $this->list->row);

    }
}
