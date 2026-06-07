<?php

namespace Verba\Mod\Search\Block\Agent;

use Verba\Block\Html;

class Input extends Html
{

    public $templates = [
        'content' => '/search/agent/input/wrap.tpl',
    ];

    public $q = '';
    public $hash = false;

    function prepare()
    {
        $this->addScripts(['agentInput', 'search']);
        //$this->addCSS(array('agentInput', 'search'));
    }

    function build()
    {
        try {
            $jsCfg = array(
                'url' => array(
                    'create' => '/search/pa',
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
