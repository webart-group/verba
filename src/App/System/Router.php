<?php
namespace Verba\App\System;

use Verba\Exception\Routing;
use Verba\Request\Http\Router as VerbaRouter;
use Verba\Response\Json;

class Router extends VerbaRouter
{
    function route()
    {
        switch ($this->request->node) {
            case 'tools':
                $router = new \system_tools($this->rq->shift());
                break;
        }

        $className = "\\" . __NAMESPACE__ . "\\" . $this->request->urlFragmentsToClass();
        if(class_exists($className)) {
            $router = new $className($this->rq->shift());
        }

        if (!isset($router)) {
            throw new Routing();
        }

        $response = $router->route();

        return $response;
    }
}
