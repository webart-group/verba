<?php

namespace Verba\Mod\Acp\Router;


class Ui extends \Verba\Request\Http\Router
{

    function route()
    {
        switch ($this->rq->node) {
            case 'gettree':
                $router = new \Verba\Mod\Acp\Block\Ui\Tree($this->rq->shift());
                break;
        }

        if (!isset($router)) {
            throw new \Verba\Exception\Routing();
        }

        $gen = $router->route();

        return $gen;
    }
}
