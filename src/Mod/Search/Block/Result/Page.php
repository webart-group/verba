<?php

namespace Mod\Search\Block\Result;

use \Mod\Search\Block\Agent\Input as AgentInput;

class Page extends \Verba\Block\Html
{
    public $templates = array(
        'content' => '/search/result/wrap.tpl',
        'results' => '/search/result/results.tpl',
        'empty-message' => '/search/result/empty-message.tpl',
    );

    public $q;
    public $hash;

    function init()
    {
        $this->addItems(array(
            'SEARCH_LIST' => new FoundedList($this, [
                'ot' => 'product',
                'cfg' => 'public products',
                'dcfg' => false,
                'q' => $this->q,
            ]),
            'SEARCH_AGENT_INPUT' => new AgentInput($this, array('q' => $this->q, 'hash' => $this->hash))
        ));
    }

    function prepare()
    {
        parent::prepare();
        self::getBlockByRole('search-agent-page')->mute();
    }

    function build()
    {
        try {
            $this->tpl->assign(array(
                'SEARCH_FILTERS' => '',
                'SEARCH_FILTERS_SIGN' => empty($this->tpl->PARSEVARS['SEARCH_FILTERS']) ? ' no-filters' : '',
                'LIST_COLUMNS_SIGN' => empty($this->tpl->PARSEVARS['SEARCH_FILTERS']) ? ' col-4' : ' col-3',
            ));

            if (!isset($this->items['SEARCH_LIST'])
                || $this->items['SEARCH_LIST']->list->getNumRows() == 0) {
                $this->tpl->assign(array(
                    'SEARCH_EMPTY_SIGN' => ' search-empty',
                    'LIST_GREEN_OPTIONS' => '',
                ));
                if (isset($this->items['SEARCH_LIST']->list) && is_object($this->items['SEARCH_LIST']->list)) {
                    $this->tpl->parse('SEARCH_RESULTS_BLOCK', 'empty-message');
                } else {
                    $this->tpl->assign(array('SEARCH_RESULTS_BLOCK' => '',));
                }
            } else {
                $this->tpl->assign(array(
                    'SEARCH_EMPTY_SIGN' => '',
                    'LIST_GREEN_OPTIONS' => $this->items['SEARCH_LIST']->optionsBlock->content,
                ));
                $this->tpl->parse('SEARCH_RESULTS_BLOCK', 'results');
            }

            $this->content = $this->tpl->parse(false, 'content');
        } catch (\Exception $e) {
            $sr = $e->getMessage();
        }

        return $this->content;
    }
}

?>