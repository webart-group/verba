<?php

namespace Mod\Otype\Router;

use Mod\ACP\Router\ObjectType;

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
