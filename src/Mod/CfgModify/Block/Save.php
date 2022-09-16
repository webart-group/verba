<?php

namespace Verba\Mod\CfgModify\Block;

use Verba\Mod\Acp;

class Save extends \Verba\Block\Raw
{

    public $modcode;

    function init()
    {
        $this->addHeader('Location', Acp::i()->cfg['url']);
    }

    function build()
    {
        $mod = \Verba\_mod($this->modcode);
        $modCfg = \Verba\_mod('cfgmodify');
        $modCfg->targetMod = $mod;
        $modCfg->customizeCfgNow();
        return $this->content;
    }

}
