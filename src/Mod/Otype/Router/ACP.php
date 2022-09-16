<?php

namespace Verba\Mod\Otype\Router;

use Verba\Mod\Acp\Router\ObjectType;

class ACP extends \Verba\Request\Http\Router
{

    function route()
    {
        switch ($this->request->node) {
            case 'api':
                $router = new ACP\API($this->request->shift());
                break;
            default:
                $router = new ACP\Otype($this->request);
        }

        $h = $router->route();

        return $h;
    }
}
