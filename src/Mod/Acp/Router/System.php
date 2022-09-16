<?php

namespace Verba\Mod\Acp\Router;

class System extends \Verba\Request\Http\Router
{

    function route()
    {

        switch ($this->request->node) {
            case 'tools':
                $router = new \system_tools($this->rq->shift());
                break;
        }

        if (!isset($router)) {
            throw new \Verba\Exception\Routing();
        }

        $response = $router->route();

        return $response;
    }
}
