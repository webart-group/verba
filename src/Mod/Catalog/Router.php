<?php
namespace Verba\Mod\Catalog;

use Verba\Mod\Catalog\routes\GoodsCatalogRouter;


class Router extends \Verba\Request\Http\Router
{
    function route()
    {
        switch ($this->rq->node) {
            case 'get-map':
                $b = new \Verba\Mod\Catalog\Map($this->rq->shift());
                break;
            default:
                $b = new GoodsCatalogRouter($this->rq);
        }

        return $b->route();
    }
}
