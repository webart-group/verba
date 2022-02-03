<?php

namespace Mod\CfgModify\Block;

class Save extends \Block\Raw
{

    public $modcode;

    function init()
    {
        $this->addHeader('Location', \Verba\Hive::getBackURL());
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
