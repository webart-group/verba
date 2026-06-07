<?php

namespace Verba\Mod\Search\Block\Agent;


class Page extends \Verba\Block\Html
{
    public $role = 'search-agent-page';

    public $templates = array(
        'content' => '/search/agent/page/wrap.tpl',
        'item' => '/search/agent/page/item.tpl',
    );
    public $jsCfg = array(
        'otCfg' => array(),
    );

    function prepare()
    {
        $this->addScripts('search', 'search');
        //$this->addCSS('search', 'search');
    }

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
