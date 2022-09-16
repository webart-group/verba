<?php

namespace Verba\Mod\Links\Block;

class Node extends \Verba\Block\Json
{
    public $lkcfg = '';

    function build()
    {
        $cfg = array(
            'cfgName' => $this->lkcfg,
        );
        if (isset($_REQUEST['nodeId'])) {
            $cfg['rq'] = array('nodeId' => $_REQUEST['nodeId']);
        }
        $this->content = \Verba\_mod('links')->loadNode($cfg);
        return $this->content;
    }
}
