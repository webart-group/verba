<?php

namespace Verba\Mod\Search\Block\Index;

use Verba\Mod\Search\Block\HtmlIncludes;

class Page extends HtmlIncludes
{
    function prepare()
    {
        parent::prepare();
        unset($this->items[0]->items['SEARCH_LIST']);
        self::getBlockByRole('search-agent-page')->mute();
    }

    function build()
    {
        $this->content = $this->items[0]->content;
        return $this->content;
    }
}
