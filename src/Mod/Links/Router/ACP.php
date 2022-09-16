<?php
namespace Verba\Mod\Links\Router;

class ACP extends \Verba\Request\Http\Router
{

    public $lcfg = '';

    function route()
    {
        $this->lcfg = \Verba\_mod('links')->getCfg($this->lcfg);

        if (!is_array($this->lcfg)) {
            throw new \Verba\Exception\Routing();
        }

        $cfg = array('lcfg' => $this->lcfg);
        $rq = $this->rq->shift();
        $rq->action = $this->rq->node;
        switch ($this->rq->node) {
            case 'initui':
                $h = new \Verba\Mod\Links\Block\InitUI($rq, $cfg);
                break;
            case 'node':
                $h = new \Verba\Mod\Links\Block\Node($rq, $cfg);
                break;
            case 'link':
            case 'create':
            case 'add':
            case 'new':
                $rq->action = 'create';
                $h = new \Verba\Mod\Links\Block\Link($rq, $cfg);
                break;

            case 'edit':
            case 'modify':
            case 'update':
                $rq->action = 'update';
                $h = new \Verba\Mod\Links\Block\Update($rq, $cfg);
                break;
            case 'unlink':
            case 'delete':
            case 'remove':
                $rq->action = 'remove';
                $h = new \Verba\Mod\Links\Block\Unlink($rq, $cfg);
                break;
        }
        if (!isset($h)) {
            throw new \Verba\Exception\Routing();
        }
        $r = $h->route();
        return $r;
    }
}
