<?php

namespace Verba\Mod\Search\Block\Agent;

use Verba\Mod\Search\Block\HtmlIncludes;

class Page extends HtmlIncludes
{
    public $role = 'search-agent-page';

    public $templates = array(
        'content' => '/search/agent/page/wrap.tpl',
        'item' => '/search/agent/page/item.tpl',
    );
    public $jsCfg = array(
        'otCfg' => array(),
    );

    function build()
    {
        try {
            $this->tpl->assign(array(
                'ITEM_SELECTOR_SIGN' => 'default',
            ));
            $this->tpl->parse('ITEMS_TEMPLATE', 'item');
            $this->tpl->assign(array(
                'JS_CFG' => \json_encode([]),
            ));

            $this->content = $this->tpl->parse(false, 'content');

        } catch (\Exception $e) {
            $this->content = 'Search unexpected results';
            $this->log()->error($e);
        }

        return $this->content;
    }
}
