<?php
namespace Mod\Notifier;

class Router extends \Verba\Request\Http\Router {

    function route()
    {
        $rq = $this->rq->shift();
        switch($rq->node){
            case 'ping':
                $h = new Block\Ping($rq->shift());
                break;
        }
        if(!isset($h)){
            throw new \Exception\Routing();
        }

        return $h;
    }
}

