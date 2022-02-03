<?php
namespace Mod\Links\Router;

class ACP extends \Verba\Request\Http\Router
{

    public $lcfg = '';

    function route()
    {
        $this->lcfg = \Verba\_mod('links')->getCfg($this->lcfg);

        if (!is_array($this->lcfg)) {
            throw new \Exception\Routing();
        }

        $cfg = array('lcfg' => $this->lcfg);
        $rq = $this->rq->shift();
        $rq->action = $this->rq->node;
        switch ($this->rq->node) {
            case 'initui':
                $h = new \Mod\Links\Block\InitUI($rq, $cfg);
                break;
            case 'node':
                $h = new \Mod\Links\Block\Node($rq, $cfg);
                break;
            case 'link':
            case 'create':
            case 'add':
            case 'new':
                $rq->action = 'create';
                $h = new \Mod\Links\Block\Link($rq, $cfg);
                break;

            case 'edit':
            case 'modify':
            case 'update':
                $rq->action = 'update';
                $h = new \Mod\Links\Block\Update($rq, $cfg);
                break;
            case 'unlink':
            case 'delete':
            case 'remove':
                $rq->action = 'remove';
                $h = new \Mod\Links\Block\Unlink($rq, $cfg);
                break;
        }
        if (!isset($h)) {
            throw new \Exception\Routing();
        }
        $r = $h->route();
        return $r;
    }
}
