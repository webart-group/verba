<?php

namespace Verba\Mod\Search\Block;

class HtmlIncludes extends \Verba\Block\Html
{

    function prepare()
    {
        parent::prepare();
        $this->addScripts('search', 'search');
        $this->addCSS('search', 'search');
    }
}
