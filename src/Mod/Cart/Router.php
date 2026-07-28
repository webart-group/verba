<?php

namespace Verba\Mod\Cart;

use Verba\Request\Http\Router as HttpRouter;
use Verba\Exception\Routing;

class Router extends HttpRouter
{

    function route()
    {
        try {
            $rq = clone $this->request;
            switch ($rq->node) {
                case 'item':
                    $h = new Routs\ItemActions($rq->shift());
                    break;
                default :
                    $h = new Routs\CartActions($rq);
                    break;
            }
        } catch (\Exception $e) {
            throw new Routing('Bad request: ' . $e->getMessage());
        }

        $r = $h->route();
        return $r;
    }
}
