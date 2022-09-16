<?php
namespace Verba\Mod\Notifier;

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
            throw new \Verba\Exception\Routing();
        }

        return $h;
    }
}

