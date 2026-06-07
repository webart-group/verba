<?php

namespace Verba\Mod\Search\Block\Index;

use Verba\Block\Html;

class Page extends Html
{
    function prepare()
    {
        $this->addScripts('search', 'search');
        $this->addCSS('search', 'search');

        unset($this->items[0]->items['SEARCH_LIST']);
        self::getBlockByRole('search-agent-page')->mute();
    }

    function build()
    {
        $this->content = $this->items[0]->content;
        return $this->content;
    }
}
