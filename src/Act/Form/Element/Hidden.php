<?php

namespace Verba\Act\Form\Element;

use \Html\Hidden as HtmlHidden;

class Hidden extends HtmlHidden
{
    public $hidden = true;

    function makeE()
    {
        $this->fire('makeE');
        $hidden = new \Html\Hidden($this->exportAsCfg());
        $this->aef->addHidden($hidden);
        $this->fire('makeEFinalize');
    }
}
