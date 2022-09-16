<?php

namespace Verba\Mod\Search\Block\Agent;

use Verba\Mod\Search\Block\HtmlIncludes;

class Input extends HtmlIncludes
{

    public $templates = [
        'content' => '/search/agent/input/wrap.tpl',
    ];

    public $q = '';
    public $hash = false;

    function prepare()
    {
        parent::prepare();
        $this->addScripts(array('agentInput', 'search'));
        $this->addCSS(array('agentInput', 'search'));
    }

    function build()
    {
        try {
            $jsCfg = array(
                'url' => array(
                    'create' => '/search/create',
                    'result' => '/search'
                ),
                'q' => htmlspecialchars($this->q),
                'hash' => $this->hash,
            );
            $this->tpl->assign(array(
                'JS_CFG' => \json_encode($jsCfg),
            ));
            $this->content = $this->tpl->parse(false, 'content');

        } catch (\Exception $e) {
            $this->content = $e->getMessage();
            throw $e;
        }
        return $this->content;
    }
}
